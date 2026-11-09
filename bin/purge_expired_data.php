<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM chat_sessions WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    $chats = $pdo->query('SELECT ROW_COUNT()')->fetchColumn();
    $pdo->exec('DELETE FROM rate_limits WHERE window_started_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    $pdo->exec('DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)');
    $pdo->commit();
    printf("Expired sessions removed: %d\n", (int) $chats);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Cleanup failed. Check the database configuration and schema.\n");
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
