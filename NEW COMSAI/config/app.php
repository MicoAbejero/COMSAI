<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '' || getenv($name) !== false) {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
    }
}

function app_env(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function h(string|int|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('NEWCOMSAISESSID');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function app_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'; object-src 'none'");
}

function csrf_token(): string
{
    start_app_session();
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function verify_csrf(): bool
{
    start_app_session();
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

function require_admin(): array
{
    start_app_session();
    if (!isset($_SESSION['admin_id'], $_SESSION['admin_role'])) {
        header('Location: /NEW%20COMSAI/login.php');
        exit;
    }
    return ['id' => (int) $_SESSION['admin_id'], 'role' => (string) $_SESSION['admin_role'], 'name' => (string) $_SESSION['admin_name']];
}

function check_rate_limit(PDO $pdo, string $scope, string $identity, int $limit, int $windowSeconds): bool
{
    $key = hash_hmac('sha256', $scope . "\0" . $identity, app_env('APP_KEY', 'new-comsai-local-development-key'));
    $statement = $pdo->prepare(
        'INSERT INTO rate_limits (key_hash, window_started_at, request_count)
         VALUES (:key_hash, NOW(), 1)
         ON DUPLICATE KEY UPDATE
           request_count = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL ' . $windowSeconds . ' SECOND), 1, request_count + 1),
           window_started_at = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL ' . $windowSeconds . ' SECOND), NOW(), window_started_at)'
    );
    $statement->execute(['key_hash' => $key]);
    $check = $pdo->prepare('SELECT request_count FROM rate_limits WHERE key_hash = :key_hash');
    $check->execute(['key_hash' => $key]);
    return (int) $check->fetchColumn() <= $limit;
}

if (app_env('APP_ENV', 'local') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
