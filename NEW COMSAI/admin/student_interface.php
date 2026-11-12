<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once dirname(__DIR__) . '/includes/site_settings.php';
app_security_headers();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function student_interface_reply(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') student_interface_reply(['ok' => false, 'error' => 'Method not allowed.'], 405);
if (!verify_csrf()) student_interface_reply(['ok' => false, 'error' => 'Form expired. Refresh and try again.'], 419);
$admin = require_admin();
if ($admin['role'] !== 'admin') student_interface_reply(['ok' => false, 'error' => 'Administrator access required.'], 403);

$action = (string) ($_POST['action'] ?? '');
if (!in_array($action, ['enable', 'disable'], true)) student_interface_reply(['ok' => false, 'error' => 'Choose enable or disable.'], 422);

try {
    $pdo = db();
    $enabled = $action === 'enable';
    $setting = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES ('student_interface_enabled', :value) ON DUPLICATE KEY UPDATE setting_value = :updated_value");
    $value = $enabled ? '1' : '0';
    $setting->execute(['value' => $value, 'updated_value' => $value]);
    admin_log($pdo, $admin['id'], $enabled ? 'enable' : 'disable', 'student-interface');
    student_interface_reply(['ok' => true, 'enabled' => $enabled, 'message' => $enabled ? 'Student Interface is enabled.' : 'Student Interface is disabled.']);
} catch (Throwable $error) {
    error_log('NEW COMSAI student interface setting failed: ' . $error->getMessage());
    student_interface_reply(['ok' => false, 'error' => 'Could not update the Student Interface setting.'], 500);
}
