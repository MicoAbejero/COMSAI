<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        fwrite(STDERR, "An administrator already exists. No account was created.\n");
        exit(1);
    }
    fwrite(STDOUT, 'Administrator name: ');
    $name = trim((string) fgets(STDIN));
    fwrite(STDOUT, 'Administrator email: ');
    $email = strtolower(trim((string) fgets(STDIN)));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fwrite(STDERR, "A name and valid email are required.\n");
        exit(1);
    }
    $password = bin2hex(random_bytes(10));
    $insert = $pdo->prepare("INSERT INTO admins (name, email, password_hash, role) VALUES (:name, :email, :hash, 'admin')");
    $insert->execute(['name' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Administrator created. Save this one-time password now: {$password}\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Administrator setup failed. Check the database connection and schema.\n");
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
