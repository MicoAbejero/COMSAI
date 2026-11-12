<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin = require_admin();
$pdo = db();
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf()) {
        $error = 'Form expired. Refresh and try again.';
    } else try {
        $action = (string) ($_POST['action'] ?? 'save');
        if ($action === 'delete') {
            if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may delete announcements.');
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Invalid announcement.');
            $pdo->prepare('DELETE FROM announcements WHERE id=:id')->execute(['id'=>$id]);
            admin_log($pdo, $admin['id'], 'delete', 'announcement', (int)$id);
        } elseif (in_array($action, ['bulk_delete','bulk_publish','bulk_unpublish'], true)) {
            if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may manage multiple announcements.');
            $submittedIds = $_POST['ids'] ?? [];
            if (!is_array($submittedIds) || $submittedIds === []) throw new RuntimeException('Select at least one announcement.');
            $ids = array_values(array_unique(array_filter(array_map(static fn($value) => filter_var($value, FILTER_VALIDATE_INT), $submittedIds), static fn($id) => $id !== false && $id > 0)));
            if ($ids === []) throw new RuntimeException('Select at least one valid announcement.');
            $publicationTimestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Taipei')))->format('Y-m-d H:i:s');
            $pdo->beginTransaction();
            try {
                foreach ($ids as $id) {
                    if ($action === 'bulk_delete') {
                        $statement = $pdo->prepare('DELETE FROM announcements WHERE id=:id');
                        $statement->execute(['id'=>$id]);
                        if ($statement->rowCount()) admin_log($pdo, $admin['id'], 'delete', 'announcement', (int)$id);
                    } else {
                        $status = $action === 'bulk_publish' ? 'published' : 'draft';
                        $statement = $pdo->prepare('UPDATE announcements SET status=:status, publish_at=CASE WHEN :publish_status=\'published\' THEN COALESCE(publish_at,:publication_timestamp) ELSE publish_at END WHERE id=:id');
                        $statement->execute(['status'=>$status,'publish_status'=>$status,'publication_timestamp'=>$publicationTimestamp,'id'=>$id]);
                        if ($statement->rowCount()) admin_log($pdo, $admin['id'], $action === 'bulk_publish' ? 'publish' : 'unpublish', 'announcement', (int)$id);
                    }
                }
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $exception;
            }
        } elseif (in_array($action, ['publish','unpublish'], true)) {
            if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may publish announcements.');
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Invalid announcement.');
            $status = $action === 'publish' ? 'published' : 'draft';
            $publicationTimestamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Taipei')))->format('Y-m-d H:i:s');
            $pdo->prepare('UPDATE announcements SET status=:status, publish_at=CASE WHEN :publish_status=\'published\' THEN COALESCE(publish_at,:publication_timestamp) ELSE publish_at END WHERE id=:id')->execute(['status'=>$status,'publish_status'=>$status,'publication_timestamp'=>$publicationTimestamp,'id'=>$id]);
            admin_log($pdo, $admin['id'], $action, 'announcement', (int)$id);
        } else {
            $submittedId = trim((string) ($_POST['id'] ?? ''));
            $id = null;
            if ($submittedId !== '') {
                $id = filter_var($submittedId, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) throw new RuntimeException('Invalid announcement.');
            }
            $title = trim((string)($_POST['title'] ?? ''));
            $content = trim((string)($_POST['content'] ?? ''));
            $url = trim((string)($_POST['source_url'] ?? ''));
            $publishAt = trim((string)($_POST['publish_at'] ?? '')) ?: null;
            $expiresAt = trim((string)($_POST['expiration_date'] ?? '')) ?: null;
            $status = $admin['role'] === 'admin' && ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
            if ($title === '' || strlen($title) > 200 || $content === '' || strlen($content) > 100000) throw new RuntimeException('Title and announcement content are required.');
            if ($url !== '' && (filter_var($url, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true))) throw new RuntimeException('Source URL must use HTTP or HTTPS.');
            foreach ([$publishAt, $expiresAt] as $dateTime) {
                if ($dateTime === null) continue;
                if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $dateTime, $parts)
                    || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
                    || (int) $parts[4] > 23 || (int) $parts[5] > 59) {
                    throw new RuntimeException('Enter valid publication and expiration dates and times.');
                }
            }
            if ($publishAt !== null) $publishAt = str_replace('T', ' ', $publishAt) . ':00';
            if ($expiresAt !== null) $expiresAt = str_replace('T', ' ', $expiresAt) . ':00';
            if ($publishAt && $expiresAt && $expiresAt < $publishAt) throw new RuntimeException('Expiration must not be before publication.');
            if ($id) {
                $exists = $pdo->prepare('SELECT COUNT(*) FROM announcements WHERE id=:id');
                $exists->execute(['id'=>$id]);
                if (!(int) $exists->fetchColumn()) throw new RuntimeException('Announcement not found.');
                $statement = $pdo->prepare('UPDATE announcements SET title=:title,content=:content,source_url=:url,publish_at=:publish,expiration_date=:expires,status=:status WHERE id=:id');
                $statement->execute(['title'=>$title,'content'=>$content,'url'=>$url?:null,'publish'=>$publishAt,'expires'=>$expiresAt,'status'=>$status,'id'=>$id]);
            } else {
                $statement = $pdo->prepare('INSERT INTO announcements (title,content,source_url,publish_at,expiration_date,status,created_by) VALUES (:title,:content,:url,:publish,:expires,:status,:admin)');
                $statement->execute(['title'=>$title,'content'=>$content,'url'=>$url?:null,'publish'=>$publishAt,'expires'=>$expiresAt,'status'=>$status,'admin'=>$admin['id']]);
                $id = (int)$pdo->lastInsertId();
            }
            admin_log($pdo, $admin['id'], 'save', 'announcement', $id);
        }
        $returnState = [];
        foreach (['q', 'status'] as $stateKey) {
            $stateValue = trim((string) ($_POST['return_' . $stateKey] ?? ''));
            if ($stateValue !== '') $returnState[$stateKey] = $stateValue;
        }
        $returnState['saved'] = '1';
        header('Location: announcements.php?' . http_build_query($returnState));
        exit;
    } catch (Throwable $exception) {
        error_log('NEW COMSAI announcement save failed: ' . $exception->getMessage());
        $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Could not save this announcement.';
    }
}

$edit = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($editId) {
    $statement = $pdo->prepare('SELECT * FROM announcements WHERE id=:id');
    $statement->execute(['id'=>$editId]);
    $edit = $statement->fetch() ?: null;
}
$defaultPublishAt = (new DateTimeImmutable('now', new DateTimeZone('Asia/Taipei')))->format('Y-m-d\\TH:i');
$q = trim((string)($_GET['q'] ?? ''));
$statusFilter = in_array($_GET['status'] ?? '', ['draft','published'], true) ? (string)$_GET['status'] : '';
$filterParams = array_filter(['q' => $q, 'status' => $statusFilter], static fn ($value): bool => $value !== '');
$rowsQuery = $pdo->query('SELECT * FROM announcements ORDER BY updated_at DESC');
$rows = $rowsQuery->fetchAll();
admin_layout_start('Announcements', 'announcements.php');
?>
<?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php elseif (isset($_GET['saved'])): ?><div class="success" role="status">Changes saved.</div><?php endif; ?>
<section class="form-panel admin-form">
  <h2><?= $edit ? 'Edit announcement' : 'Add announcement' ?></h2>
  <form method="post" data-confirm="Save this announcement?">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>"><input type="hidden" name="return_q" value="<?= h($q) ?>"><input type="hidden" name="return_status" value="<?= h($statusFilter) ?>">
    <div class="field"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= h($edit['title'] ?? '') ?>"></div>
    <div class="field"><label for="content">Announcement</label><textarea id="content" name="content" maxlength="100000" required><?= h($edit['content'] ?? '') ?></textarea></div>
    <div class="form-grid announcement-date-grid">
      <div class="field announcement-source-url"><label for="source_url">Official source URL (optional)</label><input id="source_url" name="source_url" type="url" maxlength="2048" value="<?= h($edit['source_url'] ?? '') ?>"></div>
      <div class="field announcement-publish-at"><label for="publish_at">Publication date and time</label><input id="publish_at" name="publish_at" type="datetime-local" value="<?= h(isset($edit['publish_at']) ? date('Y-m-d\\TH:i', strtotime($edit['publish_at'])) : $defaultPublishAt) ?>"></div>
      <div class="field announcement-expiration"><label for="expiration_date">Expiration date and time</label><input id="expiration_date" name="expiration_date" type="datetime-local" value="<?= h(isset($edit['expiration_date']) ? date('Y-m-d\\TH:i', strtotime($edit['expiration_date'])) : '') ?>"></div>
    </div>
    <?php if ($admin['role'] === 'admin'): ?><div class="field"><label for="status">Publication</label><select id="status" name="status"><option value="draft" <?= ($edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($edit['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Published</option></select></div><?php else: ?><p class="subtle">Editors save drafts for administrator review.</p><?php endif; ?>
    <div class="form-actions"><button class="primary-button" id="announcement-save-button" type="submit"><?= $admin['role'] === 'admin' && ($edit['status'] ?? 'draft') === 'published' ? 'Publish' : 'Save draft' ?></button><a class="secondary-button" id="announcement-cancel" href="announcements.php<?= $filterParams ? '?' . h(http_build_query($filterParams)) : '' ?>" data-confirm="Cancel and leave this form? Unsaved changes will be lost.">Cancel</a></div>
  </form>
</section>
<section class="admin-section">
  <div class="section-title"><div><h2>Announcements</h2><p>Expired notices are excluded from current chatbot answers. Search by title or announcement text.</p></div></div>
  <form method="get" class="filter-bar" data-announcement-search>
    <div class="field"><label for="q">Search announcements</label><input id="q" name="q" value="<?= h($q) ?>" autocomplete="off"></div>
    <div class="field"><label for="filter-status">Publication</label><select id="filter-status" name="status"><option value="">All</option><option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option></select></div>
  </form>
  <?php if ($admin['role'] === 'admin'): ?>
  <form id="announcement-bulk-form" method="post" class="knowledge-bulk-toolbar"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="return_q" value="<?= h($q) ?>"><input type="hidden" name="return_status" value="<?= h($statusFilter) ?>"><span id="announcement-selection-count" aria-live="polite">0 selected</span><button class="secondary-button" type="button" id="announcement-select-all">Select all</button><label class="sr-only" for="announcement-bulk-action">Bulk action</label><select id="announcement-bulk-action" name="action" required><option value="">Choose action</option><option value="bulk_publish">Publish</option><option value="bulk_unpublish">Unpublish</option><option value="bulk_delete">Delete</option></select><button class="primary-button" type="submit">Apply</button></form>
  <?php endif; ?>
  <div class="table-wrap"><table class="announcement-table <?= $admin['role'] === 'admin' ? 'has-selection' : '' ?>">
    <thead><tr><?php if ($admin['role'] === 'admin'): ?><th></th><?php endif; ?><th>Announcement</th><th>Status</th><th>Publish / expire</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?><tr data-announcement-row>
        <?php if ($admin['role'] === 'admin'): ?><td><input type="checkbox" name="ids[]" value="<?= (int)$row['id'] ?>" form="announcement-bulk-form" aria-label="Select <?= h($row['title']) ?>"></td><?php endif; ?>
        <td><strong><?= h($row['title']) ?></strong><br><small><?= h(mb_strimwidth($row['content'], 0, 100, 'â€¦')) ?></small></td>
        <td><span class="status <?= h($row['status']) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['publish_at'] ?? 'Immediate') ?> / <?= h($row['expiration_date'] ?? 'No expiry') ?></td>
        <td><a class="secondary-button small-button" href="?<?= h(http_build_query(array_merge($filterParams, ['edit' => (int) $row['id']]))) ?>" data-announcement-edit data-confirm="Edit â€˜<?= h($row['title']) ?>â€™?">Edit</a></td>
      </tr><?php endforeach; ?>
      <tr id="announcement-no-results" <?= $rows === [] ? '' : 'hidden' ?>><td colspan="<?= $admin['role'] === 'admin' ? 5 : 4 ?>" class="empty-state">No matching announcements.</td></tr>
    </tbody>
  </table></div>
</section>
<?php admin_layout_end(); ?>
