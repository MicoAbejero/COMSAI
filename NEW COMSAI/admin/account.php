<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin=require_admin();$pdo=db();$error='';
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 if(!verify_csrf())$error='Form expired. Refresh and try again.';
 else{
  $current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
  $query=$pdo->prepare('SELECT password_hash FROM admins WHERE id=:id AND is_active=1');$query->execute(['id'=>$admin['id']]);$hash=$query->fetchColumn();
  if(!is_string($hash)||!password_verify($current,$hash))$error='Current password was not accepted.';
  elseif(strlen($new)<12||strlen($new)>200)$error='Use a new password between 12 and 200 characters.';
  elseif($new!==$confirm)$error='New password confirmation does not match.';
  else{$update=$pdo->prepare('UPDATE admins SET password_hash=:hash WHERE id=:id');$update->execute(['hash'=>password_hash($new,PASSWORD_DEFAULT),'id'=>$admin['id']]);admin_log($pdo,$admin['id'],'change-password','admin',$admin['id']);session_regenerate_id(true);$success=true;}
 }
}
admin_layout_start('Administrator account','account.php');
?>
<?php if($error):?><div class="alert" role="alert"><?=h($error)?></div><?php elseif(isset($success)):?><div class="success" role="status">Password updated successfully.</div><?php endif;?>
<section class="form-panel admin-form"><h2>Change password</h2><p class="subtle">Signed in as <?=h($admin['name'])?> (<?=h($admin['role'])?>).</p><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><div class="field"><label for="current">Current password</label><input id="current" name="current_password" type="password" autocomplete="current-password" required></div><div class="field"><label for="new">New password</label><input id="new" name="new_password" type="password" minlength="12" maxlength="200" autocomplete="new-password" required></div><div class="field"><label for="confirm">Confirm new password</label><input id="confirm" name="confirm_password" type="password" minlength="12" maxlength="200" autocomplete="new-password" required></div><button class="primary-button" type="submit">Update password</button></form></section>
<?php admin_layout_end(); ?>
