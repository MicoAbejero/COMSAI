<?php

declare(strict_types=1);

function student_interface_enabled(PDO $pdo): bool
{
    try {
        $enabled = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'student_interface_enabled' LIMIT 1")->fetchColumn();
        return $enabled === false || (string) $enabled !== '0';
    } catch (Throwable $error) {
        // Keep existing installations available until the settings migration is applied.
        return true;
    }
}
