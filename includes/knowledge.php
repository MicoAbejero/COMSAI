<?php

declare(strict_types=1);

function chat_normalize_word(string $word): string
{
    if (preg_match('/[^\\W\\d_]+ies$/u', $word)) return mb_substr($word, 0, -3) . 'y';
    if (preg_match('/(?:ches|shes|xes|zes|sses)$/u', $word)) return mb_substr($word, 0, -2);
    if (!in_array($word, ['news','series','species','status','analysis','basis','crisis','thesis','address','process'], true)
        && preg_match('/[^s]s$/u', $word)) return mb_substr($word, 0, -1);
    return $word;
}

function chat_tokenize(string $text): array
{
    $words = preg_split('/[^\\p{L}\\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $ignored = ['what','when','where','which','who','how','does','about','tell','please','could','would','should','from','with','that','this','have','there','their','your','into','for','the','and','are','can','you','is','of','in','to','a','an','on','do','my','ccs','college','computer','studies','please','give','provide','information','current','currently','latest','newest'];
    $words = array_map('chat_normalize_word', $words);
    return array_values(array_unique(array_filter($words, static fn(string $word): bool => mb_strlen($word) >= 3 && !in_array($word, $ignored, true))));
}

function chat_has_word(array $words, string $queryWord): bool
{
    return in_array($queryWord, $words, true);
}

function chat_is_latest_announcement_question(string $question): bool
{
    return preg_match('/\b(latest|newest|most recent|current)\b.*\b(announcement|announcements|notice|notices)\b|\b(announcement|announcements|notice|notices)\b.*\b(latest|newest|most recent|current)\b/i', $question) === 1;
}

function chat_sources(PDO $pdo, string $question, ?int $categoryId = null): array
{
    // Questions asking for the latest announcement should always resolve to the
    // newest currently published announcement, regardless of keyword overlap.
    if (chat_is_latest_announcement_question($question)) {
        $query = $pdo->query("SELECT title,content,NULL AS search_terms,'Official CCS announcement' AS source_title,source_url,'Announcement' AS kind FROM announcements WHERE status='published' AND (publish_at IS NULL OR publish_at<=NOW()) AND (expiration_date IS NULL OR expiration_date>=NOW()) ORDER BY COALESCE(publish_at,created_at) DESC,id DESC LIMIT 1");
        return $query->fetchAll();
    }
    $terms = chat_tokenize($question);
    if ($categoryId !== null) {
        $query = $pdo->prepare("SELECT title,content,search_terms,source_title,source_url,'Knowledge base' AS kind FROM knowledge_base WHERE category_id=:category AND status='published' AND (effective_date IS NULL OR effective_date<=NOW()) AND (expiration_date IS NULL OR expiration_date>=NOW()) AND source_title IS NOT NULL AND source_title<>'' ORDER BY updated_at DESC LIMIT 100");
        $query->execute(['category'=>$categoryId]);
        $rows = $query->fetchAll();
        if ($terms === []) return array_slice($rows, 0, 1);
    } else {
        if ($terms === []) return [];
        $query = $pdo->query("SELECT title,content,search_terms,source_title,source_url,'Knowledge base' AS kind FROM knowledge_base WHERE status='published' AND (effective_date IS NULL OR effective_date<=NOW()) AND (expiration_date IS NULL OR expiration_date>=NOW()) AND source_title IS NOT NULL AND source_title<>'' ORDER BY updated_at DESC LIMIT 1000");
        $rows = $query->fetchAll();
        $announcements = $pdo->query("SELECT title,content,NULL AS search_terms,'Official announcement' AS source_title,source_url,'Announcement' AS kind FROM announcements WHERE status='published' AND (publish_at IS NULL OR publish_at<=NOW()) AND (expiration_date IS NULL OR expiration_date>=NOW()) ORDER BY publish_at DESC LIMIT 500")->fetchAll();
        $rows = array_merge($rows, $announcements);
    }

    $ranked = [];
    foreach ($rows as $row) {
        $title = chat_tokenize((string)$row['title']);
        $keywords = chat_tokenize((string)($row['search_terms'] ?? ''));
        $content = chat_tokenize((string)$row['content']);
        $score = 0;
        $titleHits = 0;
        $keywordHits = 0;
        $contentHits = 0;
        foreach ($terms as $term) {
            if (chat_has_word($title, $term)) { $score += 8; $titleHits++; }
            elseif (chat_has_word($keywords, $term)) { $score += 3; $keywordHits++; }
            elseif (chat_has_word($content, $term)) { $score += 1; $contentHits++; }
        }
        $minimumKeywordHits = count($terms) <= 2 ? 1 : 2;
        $hasStrongMatch = $titleHits > 0 || $keywordHits >= $minimumKeywordHits;
        $hasNarrowContentMatch = count($terms) <= 2 && $contentHits >= 2;
        if (!$hasStrongMatch && !$hasNarrowContentMatch) continue;
        $row['_score'] = $score;
        $row['_matched_terms'] = $titleHits + $keywordHits + $contentHits;
        $ranked[] = $row;
    }
    usort($ranked, static fn(array $a,array $b): int => ($b['_score'] <=> $a['_score']) ?: ($b['_matched_terms'] <=> $a['_matched_terms']));
    $limit = count($terms) >= 2 ? 3 : 1;
    return array_map(static function(array $row): array { unset($row['_score'], $row['_matched_terms'], $row['search_terms']); return $row; }, array_slice($ranked, 0, $limit));
}
