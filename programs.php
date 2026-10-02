<?php
require_once __DIR__.'/../config/config.php';
require_role('department_admin','system_admin');
$title='Programs';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null);
 try{
  $s=$pdo->prepare('INSERT INTO programs(department_id,program_name,program_code,duration) VALUES(?,?,?,?)');
  $s->execute([(int)$_POST['department_id'],trim($_POST['program_name']),trim($_POST['program_code']),(int)$_POST['duration']]);
  audit($pdo,'CREATE','programs',(string)$pdo->lastInsertId()); flash('success','Program created.');
 }catch(Throwable $e){flash('danger','Could not create program. Check unique code and values.');}
 redirect('admin/programs.php');
}
$deps=$pdo->query('SELECT * FROM departments WHERE status="active" ORDER BY department_name')->fetchAll();
$rows=$pdo->query('SELECT p.*,d.department_name FROM programs p JOIN departments d ON d.department_id=p.department_id ORDER BY p.program_name')->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Programs</h2>
<form method="post" class="card p-3 mb-3">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="row g-2">
<div class="col-md-4"><select class="form-select" name="department_id" required><?php foreach($deps as $d): ?><option value="<?=$d['department_id']?>"><?=e($d['department_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-3"><input class="form-control" name="program_name" placeholder="Program name" required></div>
<div class="col-md-2"><input class="form-control" name="program_code" placeholder="Code" required></div>
<div class="col-md-1"><input class="form-control" type="number" name="duration" value="4" min="1" required></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
</div></form>
<div class="card p-3"><table class="table"><tr><th>Code</th><th>Program</th><th>Department</th><th>Duration</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=e($r['program_code'])?></td><td><?=e($r['program_name'])?></td><td><?=e($r['department_name'])?></td><td><?=$r['duration']?></td></tr><?php endforeach; ?>
</table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
