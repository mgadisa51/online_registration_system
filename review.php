<?php
require_once __DIR__ . '/../config/config.php';
require_role('advisor');
$id=(int)($_GET['id'] ?? $_POST['registration_id'] ?? 0);
$title='Review Registration';
$stmt=$pdo->prepare(
'SELECT r.*,s.student_number,CONCAT(s.first_name," ",s.last_name) student_name,
        s.program_id,s.year_level,y.academic_year,sem.semester_name,
        a.advisor_id
 FROM registrations r
 JOIN students s ON s.student_id=r.student_id
 JOIN academic_years y ON y.academic_year_id=r.academic_year_id
 JOIN semesters sem ON sem.semester_id=r.semester_id
 JOIN advisors a ON a.advisor_id=r.advisor_id
 WHERE r.registration_id=? AND a.user_id=?'
);
$stmt->execute([$id,$_SESSION['user_id']]); $reg=$stmt->fetch();
if(!$reg) exit('Registration not found or not assigned to you.');

if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf($_POST['csrf_token']??null);
 $decision=$_POST['decision']??'';
 $comment=trim($_POST['comment']??'');
 if(!in_array($decision,['approved','rejected'],true)) exit('Invalid decision.');
 $stmt=$pdo->prepare(
  'UPDATE registrations SET status=?,advisor_comment=?,approved_at=IF(?="approved",NOW(),NULL)
   WHERE registration_id=? AND status IN ("pending","submitted")'
 );
 $stmt->execute([$decision,$comment,$decision,$id]);
 audit($pdo,'ADVISOR_'.$decision,'registrations',(string)$id,$comment);
 flash('success','Registration '.$decision.'.');
 redirect('advisor/registrations.php');
}
$d=$pdo->prepare('SELECT c.course_code,c.course_name,rd.credit_hour FROM registration_details rd JOIN courses c ON c.course_id=rd.course_id WHERE rd.registration_id=? ORDER BY c.course_code');
$d->execute([$id]); $details=$d->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Review Registration</h2>
<div class="card p-4 mb-3">
<p><strong>Student:</strong> <?=e($reg['student_number'].' - '.$reg['student_name'])?></p>
<p><strong>Period:</strong> <?=e($reg['academic_year'].' / '.$reg['semester_name'])?></p>
<table class="table"><thead><tr><th>Code</th><th>Course</th><th>Credit</th></tr></thead><tbody>
<?php $total=0; foreach($details as $d): $total+=(float)$d['credit_hour']; ?>
<tr><td><?=e($d['course_code'])?></td><td><?=e($d['course_name'])?></td><td><?=e((string)$d['credit_hour'])?></td></tr>
<?php endforeach; ?>
<tr><th colspan="2">Total</th><th><?=e((string)$total)?></th></tr>
</tbody></table>
<form method="post">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<input type="hidden" name="registration_id" value="<?=$id?>">
<label class="form-label">Comment</label>
<textarea class="form-control mb-3" name="comment"></textarea>
<button name="decision" value="approved" class="btn btn-success">Approve</button>
<button name="decision" value="rejected" class="btn btn-danger">Reject</button>
</form>
</div>
<?php include __DIR__.'/../includes/footer.php'; ?>
