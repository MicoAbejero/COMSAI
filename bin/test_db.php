<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $tableCount = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
    printf("Connected to %s; found %d tables.\n", $pdo->query('SELECT DATABASE()')->fetchColumn(), $tableCount);
} catch (Throwable $error) {
    fwrite(STDERR, "Database connection failed. Check MySQL, import database/new_comsai_db.sql, and review .env settings.\n");
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
