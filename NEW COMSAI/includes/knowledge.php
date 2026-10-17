<?php

declare(strict_types=1);

function chat_sources(PDO $pdo, string $question): array
{
    $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $ignored = ['what','when','where','which','who','how','does','about','tell','please','could','would','should','from','with','that','this','have','there','their','your','into','for','the','and','are','can','you','is','of','in','to','a','an','on','do','my'];
    $terms = array_values(array_unique(array_filter($words, static fn (string $word): bool => mb_strlen($word) >= 3 && !in_array($word, $ignored, true))));
    $terms = array_slice($terms, 0, 6);
    if ($terms === []) return [];

    $conditions = [];
    $params = [];
    foreach ($terms as $index => $term) {
        $conditions[] = '(title LIKE :title' . $index . ' OR content LIKE :content' . $index . ')';
        $params['title' . $index] = '%' . $term . '%';
        $params['content' . $index] = '%' . $term . '%';
    }
    $where = implode(' OR ', $conditions);
    $statement = $pdo->prepare(
        "SELECT title, content, source_title, source_url, 'Knowledge base' AS kind
         FROM knowledge_base
         WHERE status = 'published' AND (effective_date IS NULL OR effective_date <= CURDATE())
           AND (expiration_date IS NULL OR expiration_date >= CURDATE())
           AND source_title IS NOT NULL AND source_title <> '' AND ({$where})
         ORDER BY updated_at DESC LIMIT 5"
    );
    $statement->execute($params);
    $results = $statement->fetchAll();

    $announcementConditions = [];
    $announcementParams = [];
    foreach ($terms as $index => $term) {
        $announcementConditions[] = '(title LIKE :title' . $index . ' OR content LIKE :content' . $index . ')';
        $announcementParams['title' . $index] = '%' . $term . '%';
        $announcementParams['content' . $index] = '%' . $term . '%';
    }
    $announcementWhere = implode(' OR ', $announcementConditions);
    $announcementQuery = $pdo->prepare(
        "SELECT title, content, 'Official announcement' AS source_title, source_url, 'Announcement' AS kind
         FROM announcements
         WHERE status = 'published' AND (publish_at IS NULL OR publish_at <= NOW())
           AND (expiration_date IS NULL OR expiration_date >= NOW()) AND ({$announcementWhere})
         ORDER BY publish_at DESC LIMIT 3"
    );
    $announcementQuery->execute($announcementParams);
    return array_merge($results, $announcementQuery->fetchAll());
}
