<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin = require_admin();
$pdo = db();
$error = '';
$success = false;
$pending = $_SESSION['password_change'] ?? null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf()) {
        $error = 'Form expired. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'request');
        if ($action === 'confirm') {
            $pending = $_SESSION['password_change'] ?? null;
            $code = trim((string) ($_POST['email_code'] ?? ''));
            if (!is_array($pending) || (int) ($pending['expires'] ?? 0) < time()) {
                unset($_SESSION['password_change']);
                $pending = null;
                $error = 'The email code expired. Submit your password details again to request a new code.';
            } elseif ((int) ($pending['attempts'] ?? 0) >= 5) {
                unset($_SESSION['password_change']);
                $pending = null;
                $error = 'Too many incorrect codes. Submit your password details again to request a new code.';
            } elseif (!preg_match('/^\d{6}$/', $code) || !hash_equals((string) $pending['code_hash'], hash('sha256', $code))) {
                $_SESSION['password_change']['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;
                $pending = $_SESSION['password_change'];
                $error = 'Email confirmation code is incorrect.';
            } else {
                $update = $pdo->prepare('UPDATE admins SET password_hash=:hash WHERE id=:id AND is_active=1');
                $update->execute(['hash' => $pending['password_hash'], 'id' => $admin['id']]);
                if ($update->rowCount() !== 1) {
                    $error = 'Password could not be updated. Sign in again and retry.';
                } else {
                    unset($_SESSION['password_change']);
                    admin_log($pdo, $admin['id'], 'change-password', 'admin', $admin['id']);
                    session_regenerate_id(true);
                    $success = true;
                    $pending = null;
                }
            }
        } else {
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');
            $query = $pdo->prepare('SELECT email,password_hash FROM admins WHERE id=:id AND is_active=1');
            $query->execute(['id' => $admin['id']]);
            $account = $query->fetch();
            if (!$account || !password_verify($current, (string) $account['password_hash'])) {
                $error = 'Current password was not accepted.';
            } elseif (strlen($new) < 12 || strlen($new) > 200) {
                $error = 'Use a new password between 12 and 200 characters.';
            } elseif ($new !== $confirm) {
                $error = 'New password confirmation does not match.';
            } elseif (!filter_var($account['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'There is no valid email address on this admin account for confirmation.';
            } else {
                $code = (string) random_int(100000, 999999);
                $from = app_env('MAIL_FROM');
                $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
                if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) $headers .= 'From: COMSAI <' . $from . ">\r\n";
                $sent = @mail((string) $account['email'], 'COMSAI password change confirmation', "Your COMSAI password confirmation code is: {$code}\n\nThis code expires in 10 minutes. If you did not request this change, ignore this email.", $headers);
                if (!$sent) {
                    $error = 'Could not send the confirmation email. Configure PHP mail delivery and try again.';
                } else {
                    $_SESSION['password_change'] = [
                        'password_hash' => password_hash($new, PASSWORD_DEFAULT),
                        'code_hash' => hash('sha256', $code),
                        'expires' => time() + 600,
                        'attempts' => 0,
                    ];
                    $pending = $_SESSION['password_change'];
                }
            }
        }
    }
}

admin_layout_start('Administrator account', 'account.php');
?>
<?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php elseif ($success): ?><div class="success" role="status">Password updated successfully.</div><?php endif; ?>
<section class="form-panel admin-form"><h2>Change password</h2><p class="subtle">Signed in as <?= h($admin['name']) ?> (<?= h($admin['role']) ?>).</p>
<?php if (is_array($pending)): ?>
<p class="subtle">A six-digit confirmation code was sent to the email address on your admin account. Enter it to complete the password change.</p>
<form method="post" data-confirm="Confirm the password change using the emailed code?"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="confirm"><div class="field"><label for="email-code">Email confirmation code</label><input id="email-code" name="email_code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required></div><button class="primary-button" type="submit">Confirm password update</button></form>
<?php else: ?>
<form method="post" data-confirm="Send a confirmation code to your admin email to continue?"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="request"><div class="field"><label for="current">Current password</label><input id="current" name="current_password" type="password" autocomplete="current-password" required></div><div class="field"><label for="new">New password</label><input id="new" name="new_password" type="password" minlength="12" maxlength="200" autocomplete="new-password" required></div><div class="field"><label for="confirm">Confirm new password</label><input id="confirm" name="confirm_password" type="password" minlength="12" maxlength="200" autocomplete="new-password" required></div><button class="primary-button" type="submit">Update password</button></form>
<?php endif; ?></section>
<?php admin_layout_end(); ?>
