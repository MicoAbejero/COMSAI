<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
require_once dirname(__DIR__) . '/includes/search_terms.php';
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
            } elseif (in_array($action, ['bulk_delete', 'bulk_publish', 'bulk_unpublish'], true)) {
                if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may manage multiple entries.');
                $ids = $_POST['ids'] ?? [];
                if (!is_array($ids) || $ids === []) throw new RuntimeException('Select at least one entry.');
                $ids = array_values(array_unique(array_filter(array_map(static fn ($value) => filter_var($value, FILTER_VALIDATE_INT), $ids), static fn ($id) => $id !== false && $id > 0)));
                if ($ids === []) throw new RuntimeException('Select at least one valid entry.');
                $pdo->beginTransaction();
                try {
                    foreach ($ids as $id) {
                        if ($action === 'bulk_delete') {
                            $statement = $pdo->prepare('DELETE FROM knowledge_base WHERE id = :id');
                            $statement->execute(['id' => $id]);
                            if ($statement->rowCount()) admin_log($pdo, $admin['id'], 'delete', 'knowledge', (int) $id);
                        } else {
                            $status = $action === 'bulk_publish' ? 'published' : 'draft';
                            $statement = $pdo->prepare('UPDATE knowledge_base SET status = :status WHERE id = :id');
                            $statement->execute(['status' => $status, 'id' => $id]);
                            if ($statement->rowCount()) admin_log($pdo, $admin['id'], $action === 'bulk_publish' ? 'publish' : 'unpublish', 'knowledge', (int) $id);
                        }
                    }
                    $pdo->commit();
                } catch (Throwable $exception) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    throw $exception;
                }
            } elseif (in_array($action, ['publish', 'unpublish'], true)) {
                if ($admin['role'] !== 'admin') throw new RuntimeException('Only administrators may publish information.');
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id) throw new RuntimeException('Invalid entry.');
                $status = $action === 'publish' ? 'published' : 'draft';
                $update = $pdo->prepare('UPDATE knowledge_base SET status = :status WHERE id = :id');
                $update->execute(['status' => $status, 'id' => $id]);
                admin_log($pdo, $admin['id'], $action, 'knowledge', (int) $id);
            } else {
                $submittedId = trim((string) ($_POST['id'] ?? ''));
                $id = null;
                if ($submittedId !== '') {
                    $id = filter_var($submittedId, FILTER_VALIDATE_INT);
                    if ($id === false || $id < 1) throw new RuntimeException('Invalid entry.');
                }
                $title = trim((string) ($_POST['title'] ?? ''));
                $content = trim((string) ($_POST['content'] ?? ''));
                $searchTerms = trim((string) ($_POST['search_terms'] ?? ''));
                $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
                $sourceTitle = trim((string) ($_POST['source_title'] ?? ''));
                $sourceUrl = trim((string) ($_POST['source_url'] ?? ''));
                $effective = trim((string) ($_POST['effective_date'] ?? '')) ?: null;
                $expires = trim((string) ($_POST['expiration_date'] ?? '')) ?: null;
                $status = $admin['role'] === 'admin' && ($_POST['status'] ?? '') === 'published' ? 'published' : 'draft';
                if ($title === '' || strlen($title) > 200 || $content === '' || strlen($content) > 100000 || strlen($searchTerms) > 4000 || !$categoryId || $sourceTitle === '') throw new RuntimeException('Title, category, content, and source title are required; alternate terms must be under 4,000 characters.');
                $searchTerms = generated_search_terms($title, $content, $searchTerms);
                if ($sourceUrl !== '' && (filter_var($sourceUrl, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) parse_url($sourceUrl, PHP_URL_SCHEME)), ['http', 'https'], true))) throw new RuntimeException('Source URL must use HTTP or HTTPS.');
                foreach ([$effective, $expires] as $dateTime) {
                    if ($dateTime === null) continue;
                    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})$/', $dateTime, $parts)
                        || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
                        || (int) $parts[4] > 23 || (int) $parts[5] > 59) {
                        throw new RuntimeException('Enter valid publication and expiration dates and times.');
                    }
                }
                if ($effective !== null) $effective = str_replace('T', ' ', $effective) . ':00';
                if ($expires !== null) $expires = str_replace('T', ' ', $expires) . ':00';
                if ($effective && $expires && $expires < $effective) throw new RuntimeException('Expiration must not be before publication.');
                $categoryCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id = :id');
                $categoryCheck->execute(['id' => $categoryId]);
                if (!(int) $categoryCheck->fetchColumn()) throw new RuntimeException('Select an existing category.');
                if ($id) {
                    $exists = $pdo->prepare('SELECT COUNT(*) FROM knowledge_base WHERE id=:id');
                    $exists->execute(['id' => $id]);
                    if (!(int) $exists->fetchColumn()) throw new RuntimeException('Entry not found.');
                    $statement = $pdo->prepare('UPDATE knowledge_base SET category_id=:category,title=:title,content=:content,search_terms=:search_terms,source_title=:source_title,source_url=:source_url,effective_date=:effective,expiration_date=:expires,status=:status WHERE id=:id');
                    $statement->execute(['category'=>$categoryId,'title'=>$title,'content'=>$content,'search_terms'=>$searchTerms ?: null,'source_title'=>$sourceTitle,'source_url'=>$sourceUrl ?: null,'effective'=>$effective,'expires'=>$expires,'status'=>$status,'id'=>$id]);
                } else {
                    $statement = $pdo->prepare('INSERT INTO knowledge_base (category_id,title,content,search_terms,source_title,source_url,effective_date,expiration_date,status,created_by) VALUES (:category,:title,:content,:search_terms,:source_title,:source_url,:effective,:expires,:status,:admin)');
                    $statement->execute(['category'=>$categoryId,'title'=>$title,'content'=>$content,'search_terms'=>$searchTerms ?: null,'source_title'=>$sourceTitle,'source_url'=>$sourceUrl ?: null,'effective'=>$effective,'expires'=>$expires,'status'=>$status,'admin'=>$admin['id']]);
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
$categoryFilter = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);
$validCategoryIds = array_map(static fn (array $category): int => (int) $category['id'], $categories);
if ($categoryFilter === false || $categoryFilter === null || !in_array((int) $categoryFilter, $validCategoryIds, true)) $categoryFilter = 0;
$sql = 'SELECT k.*, c.name AS category_name FROM knowledge_base k JOIN categories c ON k.category_id=c.id WHERE (:q = "" OR k.title LIKE :title OR k.content LIKE :content OR k.search_terms LIKE :search_terms) AND (:status = "" OR k.status = :status_filter) ORDER BY k.updated_at DESC';
$rowsQuery = $pdo->prepare($sql);
$rowsQuery->execute(['q'=>$q,'title'=>'%'.$q.'%','content'=>'%'.$q.'%','search_terms'=>'%'.$q.'%','status'=>$statusFilter,'status_filter'=>$statusFilter]);
$rows = $rowsQuery->fetchAll();
admin_layout_start('Knowledge base', 'knowledge.php');
?>
<?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php elseif (isset($_GET['saved'])): ?><div class="success" role="status">Changes saved.</div><?php endif; ?>
<section class="form-panel admin-form"><h2><?= $edit ? 'Edit information' : 'Add information' ?></h2><form method="post" data-confirm="Save this information?"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>"><div class="form-grid"><div class="field"><label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= h($edit['title'] ?? '') ?>"></div><div class="field"><label for="category">Category</label><select id="category" name="category_id" required><option value="">Choose category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($edit['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="source_title">Source title</label><input id="source_title" name="source_title" maxlength="200" required value="<?= h($edit['source_title'] ?? '') ?>" placeholder="Official CCS memo, handbook, or page"></div><div class="field"><label for="source_url">Source URL (optional)</label><input id="source_url" name="source_url" type="url" maxlength="2048" value="<?= h($edit['source_url'] ?? '') ?>"></div><div class="field"><label for="effective">Publication date and time</label><input id="effective" name="effective_date" type="datetime-local" value="<?= h(isset($edit['effective_date']) ? date('Y-m-d\\TH:i', strtotime($edit['effective_date'])) : '') ?>"></div><div class="field"><label for="expires">Expiration date and time</label><input id="expires" name="expiration_date" type="datetime-local" value="<?= h(isset($edit['expiration_date']) ? date('Y-m-d\\TH:i', strtotime($edit['expiration_date'])) : '') ?>"></div></div><div class="field"><label for="content">Verified information</label><textarea id="content" name="content" maxlength="100000" required><?= h($edit['content'] ?? '') ?></textarea></div><div class="field"><label for="search_terms">Alternate keywords and example questions</label><textarea id="search_terms" name="search_terms" maxlength="4000" placeholder="student council, council leader, who are the officers?"><?= h($edit['search_terms'] ?? '') ?></textarea><small>Enter synonyms or sample questions separated by commas or new lines.</small></div><?php if ($admin['role'] === 'admin'): ?><div class="field"><label for="status">Publication</label><select id="status" name="status"><option value="draft" <?= ($edit['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($edit['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Published</option></select></div><?php else: ?><p class="subtle">Editors save drafts for administrator review.</p><?php endif; ?><div class="form-actions"><button class="primary-button" id="knowledge-save-button" type="submit"><?= $admin['role'] === 'admin' && ($edit['status'] ?? 'draft') === 'published' ? 'Publish' : 'Save draft' ?></button><a class="secondary-button" href="knowledge.php" data-confirm="Cancel and leave this form? Unsaved changes will be lost.">Cancel</a></div></form></section>
<section class="admin-section"><div class="section-title"><div><h2>Entries</h2><p>Results update as you type. Search accepts letters or numbers across the entry details.</p></div></div><form method="get" class="filter-bar" data-knowledge-search><div class="field"><label for="q">Search entries</label><input id="q" name="q" value="<?= h($q) ?>" autocomplete="off"></div><div class="field"><label for="filter-status">Publication</label><select id="filter-status" name="status"><option value="">All</option><option value="draft" <?= $statusFilter==='draft'?'selected':'' ?>>Draft</option><option value="published" <?= $statusFilter==='published'?'selected':'' ?>>Published</option></select></div><div class="field"><label for="filter-category">Category</label><select id="filter-category" name="category_id"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $categoryFilter === (int) $category['id'] ? 'selected' : '' ?>><?= h($category['name']) ?></option><?php endforeach; ?></select></div></form><?php if ($admin['role'] === 'admin'): ?><form id="knowledge-bulk-form" method="post" class="knowledge-bulk-toolbar"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><span id="knowledge-selection-count" aria-live="polite">0 selected</span><button class="secondary-button" type="button" id="knowledge-select-all">Select all</button><label class="sr-only" for="knowledge-bulk-action">Bulk action</label><select id="knowledge-bulk-action" name="action" required><option value="">Choose action</option><option value="bulk_publish">Publish</option><option value="bulk_unpublish">Unpublish</option><option value="bulk_delete">Delete</option></select><button class="primary-button" type="submit">Apply</button></form><?php endif; ?><div class="table-wrap"><table><thead><tr><?php if ($admin['role'] === 'admin'): ?><th></th><?php endif; ?><th>Information</th><th>Category</th><th>Status</th><th>Publish / expire</th><th>Actions</th></tr></thead><tbody id="knowledge-entries"><?php foreach ($rows as $row): ?><tr data-knowledge-row data-category-id="<?= (int) $row['category_id'] ?>"><?php if ($admin['role'] === 'admin'): ?><td><input type="checkbox" name="ids[]" value="<?= (int) $row['id'] ?>" form="knowledge-bulk-form" aria-label="Select <?= h($row['title']) ?>"></td><?php endif; ?><td><strong><?= h($row['title']) ?></strong><br><small><?= h($row['source_title']) ?></small></td><td><?= h($row['category_name']) ?></td><td><span class="status <?= h($row['status']) ?>"><?= h($row['status']) ?></span></td><td><?= h($row['effective_date'] ?? 'Immediate') ?> / <?= h($row['expiration_date'] ?? 'No expiry') ?></td><td><a class="secondary-button small-button" href="?edit=<?= (int) $row['id'] ?>">Edit</a></td></tr><?php endforeach; ?><tr id="knowledge-no-results" <?= $rows===[] ? '' : 'hidden' ?>><td colspan="<?= $admin['role'] === 'admin' ? 6 : 5 ?>" class="empty-state">No matching entries.</td></tr></tbody></table></div></section>
<?php admin_layout_end(); ?>
