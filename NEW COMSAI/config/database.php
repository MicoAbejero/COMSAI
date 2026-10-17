<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = app_env('DB_HOST', '127.0.0.1');
    $port = app_env('DB_PORT', '3306');
    $name = app_env('DB_NAME', 'new_comsai_db');
    $user = app_env('DB_USER', 'root');
    $password = app_env('DB_PASS');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $error) {
        error_log('NEW COMSAI database connection failed: ' . $error->getCode());
        throw new RuntimeException('Database connection unavailable. Check the local database configuration.', 0, $error);
    }
    return $pdo;
}
