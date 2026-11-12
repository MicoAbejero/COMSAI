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

function chat_question_needs_multiple_sources(string $question): bool
{
    if (preg_match_all('/\?/', $question) > 1 || preg_match('/[;,]/u', $question)) return true;
    if (preg_match('/,\s*[^,]+,\s*(?:and|or)\b/i', $question)) return true;
    // Topics are often joined as noun phrases, e.g. "officers and faculty members".
    return preg_match('/\b(?:and|also|additionally|in addition|as well as|plus)\b/i', $question) === 1;
}

function chat_question_parts(string $question): array
{
    $parts = preg_split('/\s+(?:and|also|additionally|in addition to|as well as|plus)\s+|[,;]+|\?+\s*/iu', trim($question), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn(string $part): bool => chat_tokenize($part) !== []));
    return count($parts) > 1 ? $parts : [$question];
}

function chat_is_all_information_request(string $question): bool
{
    return preg_match('/\b(?:all|every|everything|full list|complete list)\b/i', $question) === 1;
}

function chat_browse_category(PDO $pdo, ?int $categoryId, string $question): ?array
{
    if ($categoryId !== null) {
        $query = $pdo->prepare('SELECT id, name FROM categories WHERE id = :id LIMIT 1');
        $query->execute(['id' => $categoryId]);
        $category = $query->fetch();
        if ($category && (mb_strtolower(trim($question)) === mb_strtolower((string) $category['name']) || chat_is_all_information_request($question))) {
            return $category;
        }
    }

    if (!chat_is_all_information_request($question)) return null;
    $name = null;
    if (preg_match('/\bannouncements?\b|\bactivities\b|\bevents\b/i', $question)) {
        $name = 'Announcements and Activities';
    } elseif (preg_match('/\bgeneral\s+informations?\b|\babout\s+comsai\b/i', $question)) {
        $name = 'General Information';
    }
    if ($name === null) return null;
    $query = $pdo->prepare('SELECT id, name FROM categories WHERE name = :name LIMIT 1');
    $query->execute(['name' => $name]);
    $category = $query->fetch();
    return $category ?: null;
}

function chat_category_sources(PDO $pdo, array $category): array
{
    if (($category['name'] ?? '') === 'Announcements and Activities') {
        return $pdo->query("SELECT title,content,NULL AS search_terms,'Official CCS announcement' AS source_title,source_url,'Announcement' AS kind FROM announcements WHERE status='published' AND (publish_at IS NULL OR publish_at<=NOW()) AND (expiration_date IS NULL OR expiration_date>=NOW()) ORDER BY COALESCE(publish_at,created_at) DESC,id DESC")->fetchAll();
    }
    $query = $pdo->prepare("SELECT title,content,search_terms,source_title,source_url,'Knowledge base' AS kind FROM knowledge_base WHERE category_id=:category AND status='published' AND (effective_date IS NULL OR effective_date<=NOW()) AND (expiration_date IS NULL OR expiration_date>=NOW()) AND source_title IS NOT NULL AND source_title<>'' ORDER BY updated_at DESC,id DESC");
    $query->execute(['category' => (int) $category['id']]);
    return $query->fetchAll();
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

    $multiPart = chat_question_needs_multiple_sources($question);
    $candidateScores = [];
    foreach (chat_question_parts($question) as $part) {
        $partTerms = chat_tokenize($part);
        if ($partTerms === []) continue;
        $ranked = [];
        foreach ($rows as $index => $row) {
            $title = chat_tokenize((string)$row['title']);
            $keywords = chat_tokenize((string)($row['search_terms'] ?? ''));
            $content = chat_tokenize((string)$row['content']);
            $score = 0;
            $titleHits = 0;
            $keywordHits = 0;
            $contentHits = 0;
            foreach ($partTerms as $term) {
                if (chat_has_word($title, $term)) { $score += 8; $titleHits++; }
                elseif (chat_has_word($keywords, $term)) { $score += 3; $keywordHits++; }
                elseif (chat_has_word($content, $term)) { $score += 1; $contentHits++; }
            }
            $minimumKeywordHits = count($partTerms) <= 2 ? 1 : 2;
            $contentThreshold = count($partTerms) <= 2
                ? count($partTerms)
                : max(2, (int) ceil(count($partTerms) * 0.5));
            $hasStrongMatch = $titleHits > 0 || $keywordHits >= $minimumKeywordHits;
            $hasContentMatch = $contentHits >= $contentThreshold;
            if (!$hasStrongMatch && !$hasContentMatch) continue;
            $row['_score'] = $score;
            $row['_matched_terms'] = $titleHits + $keywordHits + $contentHits;
            $row['_row_index'] = $index;
            $ranked[] = $row;
        }
        usort($ranked, static fn(array $a,array $b): int => ($b['_score'] <=> $a['_score']) ?: ($b['_matched_terms'] <=> $a['_matched_terms']));
        foreach (array_slice($ranked, 0, 1) as $match) {
            $index = $match['_row_index'];
            if (!isset($candidateScores[$index]) || $candidateScores[$index]['_score'] < $match['_score']) $candidateScores[$index] = $match;
        }
    }
    uasort($candidateScores, static fn(array $a,array $b): int => ($b['_score'] <=> $a['_score']) ?: ($b['_matched_terms'] <=> $a['_matched_terms']));
    $limit = $multiPart ? 10 : 1;
    return array_map(static function(array $row): array { unset($row['_score'], $row['_matched_terms'], $row['_row_index'], $row['search_terms']); return $row; }, array_slice(array_values($candidateScores), 0, $limit));
}
