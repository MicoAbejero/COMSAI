<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
app_security_headers();
start_app_session();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf()) {
        $error = 'This form expired. Refresh the page and try again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        try {
            $pdo = db();
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            if (!check_rate_limit($pdo, 'admin-login-ip', $ip, 20, 900) || !check_rate_limit($pdo, 'admin-login-email', $email . $ip, 10, 900)) {
                http_response_code(429);
                $error = 'Too many sign-in attempts. Wait 15 minutes before trying again.';
            } else {
                $statement = $pdo->prepare('SELECT id, name, email, password_hash, role FROM admins WHERE email = :email AND is_active = 1 LIMIT 1');
                $statement->execute(['email' => $email]);
                $admin = $statement->fetch();
                $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION = ['admin_id' => (int) $admin['id'], 'admin_name' => $admin['name'], 'admin_role' => $admin['role'], 'csrf_token' => bin2hex(random_bytes(32))];
                    $log = $pdo->prepare("INSERT INTO activity_logs (admin_id, action, entity_type, entity_id) VALUES (:admin, 'login', 'admin', :id)");
                    $log->execute(['admin' => $admin['id'], 'id' => $admin['id']]);
                    header('Location: /NEW%20COMSAI/admin/index.php');
                    exit;
                }
                password_verify($password, $dummyHash);
                $error = 'Email or password was not recognized.';
            }
        } catch (Throwable $exception) {
            error_log('NEW COMSAI login failed: ' . $exception->getMessage());
            $error = 'The administrator service is unavailable. Check the database and try again.';
        }
    }
}
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administrator sign in | COMSAI</title><link rel="stylesheet" href="assets/css/admin.css?v=20261017-2"></head>
<body class="login-page"><main class="login-card"><a class="admin-brand" href="./"><span class="admin-mark">C</span><span><strong>COMSAI</strong><small>Private staff workspace</small></span></a><p class="eyebrow">CONTENT MANAGEMENT</p><h1>Administrator sign in</h1><p class="subtle">Sign in to manage verified CCS information.</p>
<?php if ($error !== ''): ?><div class="alert" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post" action="login.php" novalidate><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><label for="email">Email address</label><input id="email" name="email" type="email" maxlength="190" autocomplete="username" required autofocus><label for="password">Password</label><div class="password-wrap"><input id="password" name="password" type="password" autocomplete="current-password" required><button type="button" class="toggle-password" aria-label="Show password">Show</button></div><button class="primary-button full-width" type="submit">Sign in</button></form>
<p class="login-note">Administrator accounts are provisioned locally. There is no public registration.</p><a class="back-link" href="./">← Back to student chat</a></main><script src="assets/js/admin.js?v=20261017-1" defer></script></body></html>
