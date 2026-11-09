<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
start_app_session();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !verify_csrf()) {
    http_response_code(405);
    exit('Invalid logout request.');
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
}
session_destroy();
header('Location: /NEW%20COMSAI/login.php');
exit;
