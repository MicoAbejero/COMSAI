<?php

declare(strict_types=1);

function generated_search_terms(string $title, string $content, string $manual = ''): string
{
    $stop = array_fill_keys(['what','when','where','which','who','how','does','about','tell','please','could','would','should','from','with','that','this','have','there','their','your','into','for','the','and','are','can','you','is','of','in','to','a','an','on','do','my','ccs','college','computer','studies','their','will','shall','must','may','not','all','any','also','than','then','them','they','these','those','been','being','was','were','has','had','our','its','each','per','via','use','used','using'], true);
    $normalize = static fn(string $text): array => preg_split('/[^\\p{L}\\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $titleWords = array_values(array_unique(array_filter($normalize($title), static fn(string $word): bool => mb_strlen($word) >= 3 && !isset($stop[$word]))));
    $counts = [];
    foreach ($normalize($content) as $word) {
        if (mb_strlen($word) < 3 || isset($stop[$word])) continue;
        $counts[$word] = ($counts[$word] ?? 0) + 1;
    }
    arsort($counts);
    $words = array_values(array_unique(array_merge($titleWords, array_slice(array_keys($counts), 0, 80))));
    $manualTerms = trim($manual);
    $combined = implode(', ', $words) . ($manualTerms !== '' ? ', ' . $manualTerms : '');
    return mb_substr($combined, 0, 4000);
}
