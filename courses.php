<?php
require_once __DIR__.'/../config/config.php';
require_role('department_admin','system_admin');
$title='Courses';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null);
 try{
  $s=$pdo->prepare('INSERT INTO courses(course_code,course_name,credit_hour,department_id) VALUES(?,?,?,?)');
  $s->execute([trim($_POST['course_code']),trim($_POST['course_name']),(float)$_POST['credit_hour'],(int)$_POST['department_id']]);
  audit($pdo,'CREATE','courses',(string)$pdo->lastInsertId()); flash('success','Course created.');
 }catch(Throwable $e){flash('danger','Could not create course. Check code and values.');}
 redirect('admin/courses.php');
}
$deps=$pdo->query('SELECT * FROM departments WHERE status="active" ORDER BY department_name')->fetchAll();
$rows=$pdo->query('SELECT c.*,d.department_name FROM courses c JOIN departments d ON d.department_id=c.department_id ORDER BY c.course_code')->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Courses</h2>
<form method="post" class="card p-3 mb-3"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="row g-2">
<div class="col-md-2"><input class="form-control" name="course_code" placeholder="Code" required></div>
<div class="col-md-4"><input class="form-control" name="course_name" placeholder="Course name" required></div>
<div class="col-md-2"><input class="form-control" type="number" step="0.5" name="credit_hour" placeholder="Credit" required></div>
<div class="col-md-3"><select class="form-select" name="department_id" required><?php foreach($deps as $d): ?><option value="<?=$d['department_id']?>"><?=e($d['department_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-1"><button class="btn btn-primary">Add</button></div>
</div></form>
<div class="card p-3"><table class="table"><tr><th>Code</th><th>Course</th><th>Credit</th><th>Department</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=e($r['course_code'])?></td><td><?=e($r['course_name'])?></td><td><?=$r['credit_hour']?></td><td><?=e($r['department_name'])?></td></tr><?php endforeach; ?>
</table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
