<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin = require_admin();
$pdo = db();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf()) $error = 'Form expired. Refresh and try again.';
    else try {
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'delete') {
            if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may delete announcements.');
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Invalid announcement.');
            $pdo->prepare('DELETE FROM announcements WHERE id=:id')->execute(['id'=>$id]);
            admin_log($pdo, $admin['id'], 'delete', 'announcement', (int)$id);
        } elseif (in_array($action, ['publish','unpublish'], true)) {
            if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may publish announcements.');
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $pdo->prepare('UPDATE announcements SET status=:status WHERE id=:id')->execute(['status'=>$action==='publish'?'published':'draft','id'=>$id]);
            admin_log($pdo, $admin['id'], $action, 'announcement', (int)$id);
        } else {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $title = trim((string)($_POST['title'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            $url = trim((string)($_POST['source_url'] ?? ''));
            $publishAt = trim((string)($_POST['publish_at'] ?? '')) ?: null;
            $expiresAt = trim((string)($_POST['expiration_date'] ?? '')) ?: null;
            $status = $admin['role']==='admin' && ($_POST['status'] ?? '')==='published' ? 'published' : 'draft';
            if ($title==='' || strlen($title)>200 || $content==='' || strlen($content)>100000) throw new RuntimeException('Title and announcement content are required.');
            if ($url!=='' && (filter_var($url,FILTER_VALIDATE_URL)===false || !in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true))) throw new RuntimeException('Source URL must use HTTP or HTTPS.');
            foreach ([$publishAt,$expiresAt] as $dateTime) if ($dateTime!==null && strtotime($dateTime)===false) throw new RuntimeException('Enter valid publication and expiration dates.');
            if ($publishAt && $expiresAt && strtotime($expiresAt)<strtotime($publishAt)) throw new RuntimeException('Expiration must not be before publication.');
            if ($id) {
                $statement=$pdo->prepare('UPDATE announcements SET title=:title,content=:content,source_url=:url,publish_at=:publish,expiration_date=:expires,status=:status WHERE id=:id');
                $statement->execute(['title'=>$title,'content'=>$content,'url'=>$url?:null,'publish'=>$publishAt,'expires'=>$expiresAt,'status'=>$status,'id'=>$id]);
            } else {
                $statement=$pdo->prepare('INSERT INTO announcements (title,content,source_url,publish_at,expiration_date,status,created_by) VALUES (:title,:content,:url,:publish,:expires,:status,:admin)');
                $statement->execute(['title'=>$title,'content'=>$content,'url'=>$url?:null,'publish'=>$publishAt,'expires'=>$expiresAt,'status'=>$status,'admin'=>$admin['id']]);
                $id=(int)$pdo->lastInsertId();
            }
            admin_log($pdo,$admin['id'],'save','announcement',$id);
        }
        header('Location: announcements.php?saved=1'); exit;
    } catch (Throwable $exception) {
        error_log('NEW COMSAI announcement save failed: '.$exception->getMessage());
        $error=$exception instanceof RuntimeException?$exception->getMessage():'Could not save this announcement.';
    }
}
$edit=null;$editId=filter_var($_GET['edit']??null,FILTER_VALIDATE_INT);if($editId){$statement=$pdo->prepare('SELECT * FROM announcements WHERE id=:id');$statement->execute(['id'=>$editId]);$edit=$statement->fetch()?:null;}
$rows=$pdo->query('SELECT * FROM announcements ORDER BY updated_at DESC LIMIT 100')->fetchAll();
admin_layout_start('Announcements','announcements.php');
?>
<?php if($error):?><div class="alert" role="alert"><?=h($error)?></div><?php elseif(isset($_GET['saved'])):?><div class="success" role="status">Changes saved.</div><?php endif;?>
<section class="form-panel admin-form"><h2><?=$edit?'Edit announcement':'Add announcement'?></h2><form method="post" data-confirm="Save this announcement?"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="id" value="<?=h($edit['id']??'')?>"><div class="field"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?=h($edit['title']??'')?>"></div><div class="field"><label for="content">Announcement</label><textarea id="content" name="content" maxlength="100000" required><?=h($edit['content']??'')?></textarea></div><div class="form-grid"><div class="field"><label for="source_url">Official source URL (optional)</label><input id="source_url" name="source_url" type="url" maxlength="2048" value="<?=h($edit['source_url']??'')?>"></div><div class="field"><label for="publish_at">Publication date and time</label><input id="publish_at" name="publish_at" type="datetime-local" value="<?=h(isset($edit['publish_at'])?date('Y-m-d\TH:i',strtotime($edit['publish_at'])):'')?>"></div><div class="field"><label for="expiration_date">Expiration date and time</label><input id="expiration_date" name="expiration_date" type="datetime-local" value="<?=h(isset($edit['expiration_date'])?date('Y-m-d\TH:i',strtotime($edit['expiration_date'])):'')?>"></div></div><?php if($admin['role']==='admin'):?><div class="field"><label for="status">Publication</label><select id="status" name="status"><option value="draft" <?=($edit['status']??'draft')==='draft'?'selected':''?>>Draft</option><option value="published" <?=($edit['status']??'')==='published'?'selected':''?>>Published</option></select></div><?php else:?><p class="subtle">Editors save drafts for administrator review.</p><?php endif;?><div class="form-actions"><button class="primary-button">Save announcement</button><a class="secondary-button" href="announcements.php" data-confirm="Cancel and leave this form? Unsaved changes will be lost.">Cancel</a></div></form></section>
<section class="admin-section"><div class="section-title"><div><h2>Announcements</h2><p>Expired notices are excluded from current chatbot answers.</p></div></div><div class="table-wrap"><table><thead><tr><th>Announcement</th><th>Status</th><th>Publish / expire</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><strong><?=h($row['title'])?></strong><br><small><?=h(mb_strimwidth($row['content'],0,100,'…'))?></small></td><td><span class="status <?=h($row['status'])?>"><?=h($row['status'])?></span></td><td><?=h($row['publish_at']??'Immediate')?> / <?=h($row['expiration_date']??'No expiry')?></td><td><div class="table-actions"><a class="secondary-button small-button" href="?edit=<?=(int)$row['id']?>">Edit</a><?php if($admin['role']==='admin'):?><form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><input type="hidden" name="action" value="<?=$row['status']==='published'?'unpublish':'publish'?>"><button class="secondary-button small-button"><?=$row['status']==='published'?'Unpublish':'Publish'?></button></form><?php endif;?><form method="post" class="inline-form" data-confirm="Delete this announcement?"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><input type="hidden" name="action" value="delete"><button class="danger-button small-button">Delete</button></form></div></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="4" class="empty-state">No announcements yet.</td></tr><?php endif;?></tbody></table></div></section>
<?php admin_layout_end(); ?>
