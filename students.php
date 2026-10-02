<?php
require_once __DIR__.'/../config/config.php';
require_role('department_admin','system_admin');
$title='Students';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null);
 try{
  $pdo->beginTransaction();
  $role=$pdo->query("SELECT role_id FROM roles WHERE role_name='student'")->fetchColumn();
  $s=$pdo->prepare('INSERT INTO users(username,password_hash,role_id) VALUES(?,?,?)');
  $s->execute([trim($_POST['student_number']),password_hash($_POST['password'],PASSWORD_DEFAULT),$role]);
  $uid=(int)$pdo->lastInsertId();
  $s=$pdo->prepare(
   'INSERT INTO students(user_id,student_number,first_name,middle_name,last_name,gender,phone,email,department_id,program_id,year_level)
    VALUES(?,?,?,?,?,?,?,?,?,?,?)'
  );
  $s->execute([$uid,trim($_POST['student_number']),trim($_POST['first_name']),trim($_POST['middle_name']??''),trim($_POST['last_name']),
   $_POST['gender']??null,trim($_POST['phone']??''),trim($_POST['email']??''),(int)$_POST['department_id'],(int)$_POST['program_id'],(int)$_POST['year_level']]);
  $sid=(int)$pdo->lastInsertId();
  audit($pdo,'CREATE','students',(string)$sid); $pdo->commit(); flash('success','Student created.');
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('danger','Could not create student: '.$e->getMessage());}
 redirect('admin/students.php');
}
$deps=$pdo->query('SELECT * FROM departments WHERE status="active"')->fetchAll();
$programs=$pdo->query('SELECT * FROM programs WHERE status="active"')->fetchAll();
$rows=$pdo->query('SELECT s.*,d.department_name,p.program_name FROM students s JOIN departments d ON d.department_id=s.department_id JOIN programs p ON p.program_id=s.program_id ORDER BY s.student_number')->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Students</h2>
<form method="post" class="card p-3 mb-3"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="row g-2">
<div class="col-md-2"><input class="form-control" name="student_number" placeholder="Student ID" required></div>
<div class="col-md-2"><input class="form-control" name="first_name" placeholder="First name" required></div>
<div class="col-md-2"><input class="form-control" name="middle_name" placeholder="Middle name"></div>
<div class="col-md-2"><input class="form-control" name="last_name" placeholder="Last name" required></div>
<div class="col-md-2"><select class="form-select" name="gender"><option>Male</option><option>Female</option><option>Other</option></select></div>
<div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone"></div>
<div class="col-md-3"><input class="form-control" type="email" name="email" placeholder="Email"></div>
<div class="col-md-3"><select class="form-select" name="department_id" required><?php foreach($deps as $d):?><option value="<?=$d['department_id']?>"><?=e($d['department_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-3"><select class="form-select" name="program_id" required><?php foreach($programs as $p):?><option value="<?=$p['program_id']?>"><?=e($p['program_name'])?></option><?php endforeach;?></select></div>
<div class="col-md-1"><input class="form-control" type="number" name="year_level" value="1" min="1" required></div>
<div class="col-md-2"><input class="form-control" type="password" name="password" placeholder="Initial password" required></div>
<div class="col-md-12"><button class="btn btn-primary">Create Student</button></div>
</div></form>
<div class="card p-3"><table class="table"><tr><th>ID</th><th>Name</th><th>Program</th><th>Year</th><th>Status</th></tr>
<?php foreach($rows as $r): ?><tr><td><?=e($r['student_number'])?></td><td><?=e($r['first_name'].' '.$r['last_name'])?></td><td><?=e($r['program_name'])?></td><td><?=$r['year_level']?></td><td><?=e($r['status'])?></td></tr><?php endforeach; ?>
</table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
