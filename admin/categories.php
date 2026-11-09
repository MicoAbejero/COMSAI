<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin = require_admin();
if ($admin['role'] !== 'admin') { http_response_code(403); exit('Administrator access required.'); }
$pdo = db();
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf()) {
        $error = 'Form expired. Refresh and try again.';
    } else try {
        $action = (string)($_POST['action'] ?? 'save');
        if ($action === 'bulk_delete') {
            $submittedIds = $_POST['ids'] ?? [];
            if (!is_array($submittedIds) || $submittedIds === []) throw new RuntimeException('Select at least one category.');
            $ids = array_values(array_unique(array_filter(array_map(static fn($value) => filter_var($value, FILTER_VALIDATE_INT), $submittedIds), static fn($id) => $id !== false && $id > 0)));
            if ($ids === []) throw new RuntimeException('Select at least one valid category.');
            $pdo->beginTransaction();
            try {
                foreach ($ids as $id) {
                    $check = $pdo->prepare('SELECT COUNT(*) FROM knowledge_base WHERE category_id=:id');
                    $check->execute(['id'=>$id]);
                    if ((int)$check->fetchColumn() > 0) throw new RuntimeException('Selected categories must be empty before they can be deleted.');
                    $statement = $pdo->prepare('DELETE FROM categories WHERE id=:id');
                    $statement->execute(['id'=>$id]);
                    if ($statement->rowCount()) admin_log($pdo, $admin['id'], 'delete', 'category', (int)$id);
                }
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $exception;
            }
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) throw new RuntimeException('Invalid category.');
            $check = $pdo->prepare('SELECT COUNT(*) FROM knowledge_base WHERE category_id=:id');
            $check->execute(['id'=>$id]);
            if ((int)$check->fetchColumn() > 0) throw new RuntimeException('This category is in use. Move or delete its knowledge entries first.');
            $pdo->prepare('DELETE FROM categories WHERE id=:id')->execute(['id'=>$id]);
            admin_log($pdo, $admin['id'], 'delete', 'category', (int)$id);
        } else {
            $submittedId = trim((string) ($_POST['id'] ?? ''));
            $id = null;
            if ($submittedId !== '') {
                $id = filter_var($submittedId, FILTER_VALIDATE_INT);
                if ($id === false || $id < 1) throw new RuntimeException('Invalid category.');
            }
            $name = trim((string)($_POST['name'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            if ($name === '' || strlen($name) > 100) throw new RuntimeException('Enter a category name up to 100 characters.');
            if (strlen($description) > 500) throw new RuntimeException('Category descriptions must be 500 characters or fewer.');
            if ($id) {
                $exists = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE id=:id');
                $exists->execute(['id'=>$id]);
                if (!(int) $exists->fetchColumn()) throw new RuntimeException('Category not found.');
                $statement = $pdo->prepare('UPDATE categories SET name=:name,description=:description WHERE id=:id');
                $statement->execute(['name'=>$name,'description'=>$description?:null,'id'=>$id]);
            } else {
                $statement = $pdo->prepare('INSERT INTO categories (name,description) VALUES (:name,:description)');
                $statement->execute(['name'=>$name,'description'=>$description?:null]);
                $id = (int)$pdo->lastInsertId();
            }
            admin_log($pdo, $admin['id'], 'save', 'category', $id);
        }
        header('Location: categories.php?saved=1');
        exit;
    } catch (Throwable $exception) {
        error_log('NEW COMSAI category operation failed: ' . $exception->getMessage());
        $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Category name already exists or the operation failed.';
    }
}

$edit = null;
$editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
if ($editId) {
    $statement = $pdo->prepare('SELECT * FROM categories WHERE id=:id');
    $statement->execute(['id'=>$editId]);
    $edit = $statement->fetch() ?: null;
}
$q = trim((string)($_GET['q'] ?? ''));
$rowsQuery = $pdo->prepare("SELECT c.*,COUNT(k.id) AS knowledge_count FROM categories c LEFT JOIN knowledge_base k ON k.category_id=c.id WHERE (:q = '' OR c.name LIKE :name OR c.description LIKE :description) GROUP BY c.id ORDER BY c.name");
$rowsQuery->execute(['q'=>$q,'name'=>'%'.$q.'%','description'=>'%'.$q.'%']);
$rows = $rowsQuery->fetchAll();
admin_layout_start('Categories', 'categories.php');
?>
<?php if ($error): ?><div class="alert" role="alert"><?= h($error) ?></div><?php elseif (isset($_GET['saved'])): ?><div class="success" role="status">Changes saved.</div><?php endif; ?>
<section class="form-panel admin-form">
  <h2><?= $edit ? 'Edit category' : 'Add category' ?></h2>
  <form method="post" data-confirm="Save this category?"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>">
    <div class="field"><label for="name">Category name</label><input id="name" name="name" maxlength="100" required value="<?= h($edit['name'] ?? '') ?>"></div>
    <div class="field"><label for="description">Description (optional)</label><input id="description" name="description" maxlength="500" value="<?= h($edit['description'] ?? '') ?>"></div>
    <div class="form-actions"><button class="primary-button">Save category</button><?php if ($edit): ?><a class="secondary-button" href="categories.php" data-confirm="Cancel editing this category? Unsaved changes will be lost.">Cancel</a><?php endif; ?></div>
  </form>
</section>
<section class="admin-section">
  <div class="section-title"><div><h2>Categories</h2><p>Search by category name or description. Categories with knowledge entries cannot be deleted.</p></div></div>
  <form method="get" class="filter-bar" data-category-search><div class="field"><label for="category-q">Search categories</label><input id="category-q" name="q" value="<?= h($q) ?>" autocomplete="off"></div></form>
  <form id="category-bulk-form" method="post" class="knowledge-bulk-toolbar"><input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="bulk_delete"><span id="category-selection-count" aria-live="polite">0 selected</span><button class="secondary-button" type="button" id="category-select-all">Select all</button><button class="danger-button" type="submit">Delete selected</button></form>
  <div class="table-wrap"><table>
    <thead><tr><th></th><th>Name</th><th>Description</th><th>Knowledge entries</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): $inUse = (int)$row['knowledge_count'] > 0; ?><tr data-category-row>
        <td><input type="checkbox" name="ids[]" value="<?= (int)$row['id'] ?>" form="category-bulk-form" aria-label="Select <?= h($row['name']) ?>" <?= $inUse ? 'disabled title="Category contains knowledge entries"' : '' ?>></td>
        <td><strong><?= h($row['name']) ?></strong></td><td><?= h($row['description']) ?></td><td><?= (int)$row['knowledge_count'] ?></td>
        <td><a class="secondary-button small-button" href="?edit=<?= (int)$row['id'] ?>" data-confirm="Edit this category?">Edit</a></td>
      </tr><?php endforeach; ?>
      <tr id="category-no-results" <?= $rows === [] ? '' : 'hidden' ?>><td colspan="5" class="empty-state">No matching categories.</td></tr>
    </tbody>
  </table></div>
</section>
<?php admin_layout_end(); ?>
