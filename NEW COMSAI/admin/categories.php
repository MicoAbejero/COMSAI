<?php

declare(strict_types=1);

require_once __DIR__ . '/_layout.php';
$admin = require_admin();
if ($admin['role'] !== 'admin') { http_response_code(403); exit('Administrator access required.'); }
$pdo=db();$error='';
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 if(!verify_csrf())$error='Form expired. Refresh and try again.';
 else try{
  $action=(string)($_POST['action']??'save');$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT)?:null;
  if($action==='delete'){
   if(!$id)throw new RuntimeException('Invalid category.');
   $check=$pdo->prepare('SELECT COUNT(*) FROM knowledge_base WHERE category_id=:id');$check->execute(['id'=>$id]);
   if((int)$check->fetchColumn()>0)throw new RuntimeException('This category is in use. Move or delete its knowledge entries first.');
   $pdo->prepare('DELETE FROM categories WHERE id=:id')->execute(['id'=>$id]);admin_log($pdo,$admin['id'],'delete','category',$id);
  }else{
   $name=trim((string)($_POST['name']??''));$description=trim((string)($_POST['description']??''));
   if($name===''||strlen($name)>100)throw new RuntimeException('Enter a category name up to 100 characters.');
   if($id){$statement=$pdo->prepare('UPDATE categories SET name=:name,description=:description WHERE id=:id');$statement->execute(['name'=>$name,'description'=>$description?:null,'id'=>$id]);}
   else{$statement=$pdo->prepare('INSERT INTO categories (name,description) VALUES (:name,:description)');$statement->execute(['name'=>$name,'description'=>$description?:null]);$id=(int)$pdo->lastInsertId();}
   admin_log($pdo,$admin['id'],'save','category',$id);
  }
  header('Location: categories.php?saved=1');exit;
 }catch(Throwable $exception){error_log('NEW COMSAI category operation failed: '.$exception->getMessage());$error=$exception instanceof RuntimeException?$exception->getMessage():'Category name already exists or the operation failed.';}
}
$edit=null;$editId=filter_var($_GET['edit']??null,FILTER_VALIDATE_INT);if($editId){$statement=$pdo->prepare('SELECT * FROM categories WHERE id=:id');$statement->execute(['id'=>$editId]);$edit=$statement->fetch()?:null;}
$rows=$pdo->query('SELECT c.*,COUNT(k.id) AS knowledge_count FROM categories c LEFT JOIN knowledge_base k ON k.category_id=c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
admin_layout_start('Categories','categories.php');
?>
<?php if($error):?><div class="alert" role="alert"><?=h($error)?></div><?php elseif(isset($_GET['saved'])):?><div class="success" role="status">Changes saved.</div><?php endif;?>
<section class="form-panel admin-form"><h2><?=$edit?'Edit category':'Add category'?></h2><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="id" value="<?=h($edit['id']??'')?>"><div class="field"><label for="name">Category name</label><input id="name" name="name" maxlength="100" required value="<?=h($edit['name']??'')?>"></div><div class="field"><label for="description">Description (optional)</label><input id="description" name="description" maxlength="500" value="<?=h($edit['description']??'')?>"></div><div class="form-actions"><button class="primary-button">Save category</button><?php if($edit):?><a class="secondary-button" href="categories.php">Cancel</a><?php endif;?></div></form></section>
<section class="admin-section"><h2>Categories</h2><div class="table-wrap"><table><thead><tr><th>Name</th><th>Description</th><th>Knowledge entries</th><th>Actions</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td><strong><?=h($row['name'])?></strong></td><td><?=h($row['description'])?></td><td><?= (int)$row['knowledge_count']?></td><td><div class="table-actions"><a class="secondary-button small-button" href="?edit=<?=(int)$row['id']?>">Edit</a><form method="post" class="inline-form" data-confirm="Delete this unused category?"><input type="hidden" name="_csrf" value="<?=h(csrf_token())?>"><input type="hidden" name="id" value="<?=(int)$row['id']?>"><input type="hidden" name="action" value="delete"><button class="danger-button small-button" <?=((int)$row['knowledge_count']>0)?'disabled':''?>>Delete</button></form></div></td></tr><?php endforeach;?></tbody></table></div></section>
<?php admin_layout_end(); ?>
