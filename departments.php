<?php
require_once __DIR__.'/../config/config.php';
require_role('department_admin','system_admin');
$title='Departments';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null);
 $name=trim($_POST['department_name']??'');
 if($name!==''){
  try{
   $s=$pdo->prepare('INSERT INTO departments(department_name) VALUES(?)');$s->execute([$name]);
   audit($pdo,'CREATE','departments',(string)$pdo->lastInsertId()); flash('success','Department created.');
  }catch(Throwable $e){flash('danger','Could not create department.');}
 }
 redirect('admin/departments.php');
}
$rows=$pdo->query('SELECT * FROM departments ORDER BY department_name')->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Departments</h2>
<form method="post" class="card p-3 mb-3">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="input-group"><input class="form-control" name="department_name" placeholder="Department name" required>
<button class="btn btn-primary">Add</button></div></form>
<div class="card p-3"><table class="table"><tr><th>ID</th><th>Name</th><th>Status</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=$r['department_id']?></td><td><?=e($r['department_name'])?></td><td><?=e($r['status'])?></td></tr><?php endforeach; ?>
</table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
