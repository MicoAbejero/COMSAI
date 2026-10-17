<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin = require_admin();
$pdo = db();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf()) {
        $error = 'Form expired. Refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'save');
        try {
            if ($action === 'delete') {
                if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may delete information.');
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id) throw new RuntimeException('Invalid entry.');
                $delete = $pdo->prepare('DELETE FROM knowledge_base WHERE id = :id');
                $delete->execute(['id' => $id]);
                admin_log($pdo, $admin['id'], 'delete', 'knowledge', (int) $id);
            } elseif (in_array($action, ['publish', 'unpublish'], true)) {
                if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may publish information.');
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id) throw new RuntimeException('Invalid entry.');
                $status = $action === 'publish' ? 'published' : 'draft';
                $update = $pdo->prepare('UPDATE knowledge_base SET status = :status WHERE id = :id');
                $update->execute(['status' => $status, 'id' => $id]);
                admin_log($pdo, $admin['id'], $action, 'knowledge', (int) $id);
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
                $title = trim((string) ($_POST['title'] ?? ''));
                $content = trim((string) ($_POST['content'] ?? ''));
                $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
                $sourceTitle = trim((string) ($_POST['source_title'] ?? ''));
                $sourceUrl = trim((string) ($_POST['source_url'] ?? ''));
                $effective = trim((string) ($_POST['effective_date'] ?? '')) ?: null;
                $expires = trim((string) ($_POST['expiration_date'] ?? '')) ?: null;
                $status = $admin['role'] === 'admin' && ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
                if ($title === '' || strlen($title) > 200 || $content === '' || strlen($content) > 100000 || !$categoryId || $sourceTitle === '') throw new RuntimeException('Title, category, content, and source title are required.');
                if ($sourceUrl !== '' && (filter_var($sourceUrl, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) parse_url($sourceUrl, PHP_URL_SCHEME)), ['http', 'https'], true))) throw new RuntimeException('Source URL must use HTTP or HTTPS.');
                foreach ([$effective, $expires] as $date) if ($date !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)))) throw new RuntimeException('Dates must be valid calendar dates.');
                if ($effective && $expires && $effective > $expires) throw new RuntimeException('Expiration must not be earlier than the effective date.');
                $categoryCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
                $categoryCheck->execute(['id' => $categoryId]);
                if (!(int) $categoryCheck->fetchColumn()) throw new RuntimeException('Select an existing category.');
                if ($id) {
                    $statement = $pdo->prepare('UPDATE knowledge_base SET category_id=:category,title=:title,content=:content,source_title=:source_title,source_url=:source_url,effective_date=:effective,expiration_date=:expires,status=:status WHERE id=:id');
                    $statement->execute(['category'=>$categoryId,'title'=>$title,'content'=>$content,'source_title'=>$sourceTitle,'source_url'=>$sourceUrl ?: null,'effective'=>$effective,'expires'=>$expires,'status'=>$status,'id'=>$id]);
                } else {
                    $statement = $pdo->prepare('INSERT INTO knowledge_base (category_id,title,content,source_title,source_url,effective_date,expiration_date,status,created_by) VALUES (:category,:title,:content,:source_title,:source_url,:effective,:expires,:status,:admin)');
                    $statement->execute(['category'=>$categoryId,'title'=>$title,'content'=>$content,'source_title'=>$sourceTitle,'source_url'=>$sourceUrl ?: null,'effective'=>$effective,'expires'=>$expires,'status'=>$status,'admin'=>$admin['id']]);
                    $id = (int) $pdo->lastInsertId();
                }
                admin_log($pdo, $admin['id'], 'save', 'knowledge', $id);
            }
            header('Location: knowledge.php?saved=1');
            exit;
        } catch (Throwable $exception) {
            error_log('NEW COMSAI knowledge save failed: ' . $exception->getMessage());
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Could not save the information. Check that it is not in use.';
        }
    }
}
$categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$edit = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($editId) {
    $statement = $pdo->prepare('SELECT * FROM knowledge_base WHERE id = :id');
    $statement->execute(['id' => $editId]);
    $edit = $statement->fetch() ?: null;
}
$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = in_array($_GET['status'] ?? '', ['draft','published'], true) ? (string) $_GET['status'] : '';
$sql = 'SELECT k.*, c.name AS category_name FROM knowledge_base k JOIN categories c ON c.id=k.category_id WHERE (:q = "" OR k.title LIKE :title OR k.content LIKE :content) AND (:status = "" OR k.status = :status_filter) ORDER BY k.updated_at DESC LIMIT 100';
$rowsQuery = $pdo->prepare($sql);
$rowsQuery->execute(['q'=>$q,'title'=>'%'.$q.'%','content'=>'%'.$q.'%','status'=>$statusFilter,'status_filter'=>$statusFilter]);
$rows = $rowsQuery->fetchAll();
admin_layout_start('Knowledge base', 'knowledge.php');
?>
<?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php elseif (isset($_GET['saved'])): ?><div class="success" role="status">Changes saved.</div><?php endif; ?>
<section class="form-panel admin-form"><h2><?= $edit ? 'Edit information' : 'Add information' ?></h2><form method="post"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>"><div class="form-grid"><div class="field"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= h($edit['title'] ?? '') ?>"></div><div class="field"><label for="category">Category</label><select id="category" name="category_id" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($edit['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="source_title">Source title</label><input id="source_title" name="source_title" maxlength="200" required value="<?= h($edit['source_title'] ?? '') ?>" placeholder="Official CCS memo, handbook, or page"></div><div class="field"><label for="source_url">Source URL (optional)</label><input id="source_url" name="source_url" type="url" maxlength="2048" value="<?= h($edit['source_url'] ?? '') ?>"></div><div class="field"><label for="effective">Effective date</label><input id="effective" name="effective_date" type="date" value="<?= h($edit['effective_date'] ?? '') ?>"></div><div class="field"><label for="expires">Expiration date</label><input id="expires" name="expiration_date" type="date" value="<?= h($edit['expiration_date'] ?? '') ?>"></div></div><div class="field"><label for="content">Verified information</label><textarea id="content" name="content" maxlength="100000" required><?= h($edit['content'] ?? '') ?></textarea></div><?php if ($admin['role'] === 'admin'): ?><div class="field"><label for="status">Publication</label><select id="status" name="status"><option value="draft" <?= ($edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($edit['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select></div><?php else: ?><p class="subtle">Editors save drafts for administrator review.</p><?php endif; ?><div class="form-actions"><button class="primary-button" type="submit">Save information</button><?php if ($edit): ?><a class="secondary-button" href="knowledge.php">Cancel edit</a><?php endif; ?></div></form></section>
<section class="admin-section"><div class="section-title"><div><h2>Entries</h2><p>Search and filter verified content.</p></div></div><form method="get" class="filter-bar"><div class="field"><label for="q">Search title or content</label><input id="q" name="q" value="<?= h($q) ?>"></div><div class="field"><label for="filter-status">Publication</label><select id="filter-status" name="status"><option value="">All</option><option value="draft" <?= $statusFilter==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= $statusFilter==='published'?'selected':'' ?>>Published</option></select></div><button class="secondary-button" type="submit">Filter</button></form><div class="table-wrap"><table><thead><tr><th>Information</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><strong><?= h($row['title']) ?></strong><br><small><?= h($row['source_title']) ?></small></td><td><?= h($row['category_name']) ?></td><td><span class="status <?= h($row['status']) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['updated_at']) ?></td><td><div class="table-actions"><a class="secondary-button small-button" href="?edit=<?= (int) $row['id'] ?>">Edit</a><?php if ($admin['role']==='admin'): ?><form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="action" value="<?= $row['status']==='published'?'unpublish':'publish' ?>"><button class="secondary-button small-button" type="submit"><?= $row['status']==='published'?'Unpublish':'Publish' ?></button></form><?php endif; ?><form method="post" class="inline-form" data-confirm="Delete this knowledge entry?"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"><input type="hidden" name="action" value="delete"><button class="danger-button small-button" type="submit">Delete</button></form></div></td></tr><?php endforeach; ?><?php if ($rows===[]): ?><tr><td colspan="5" class="empty-state">No matching entries.</td></tr><?php endif; ?></tbody></table></div></section>
<?php admin_layout_end(); ?>
