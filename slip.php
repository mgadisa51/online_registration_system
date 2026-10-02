<?php
require_once __DIR__ . '/../config/config.php';
require_role('student');
$student=current_student($pdo); $title='Registration Slip';
$stmt=$pdo->prepare(
'SELECT r.*, y.academic_year,s.semester_name
 FROM registrations r
 JOIN academic_years y ON y.academic_year_id=r.academic_year_id
 JOIN semesters s ON s.semester_id=r.semester_id
 WHERE r.student_id=? AND r.status="approved"
 ORDER BY r.registration_id DESC LIMIT 1'
);
$stmt->execute([$student['student_id']]); $reg=$stmt->fetch();
$details=[];
if($reg){
 $d=$pdo->prepare('SELECT c.course_code,c.course_name,rd.credit_hour FROM registration_details rd JOIN courses c ON c.course_id=rd.course_id WHERE rd.registration_id=? ORDER BY c.course_code');
 $d->execute([$reg['registration_id']]); $details=$d->fetchAll();
}
include __DIR__.'/../includes/header.php';
?>
<h2>Electronic Registration Slip</h2>
<?php if(!$reg): ?><div class="alert alert-warning">No approved registration slip is available.</div>
<?php else: ?>
<div class="card p-4" id="slip">
<h3 class="text-center">Borana University</h3>
<h5 class="text-center">Online Student Registration Slip</h5>
<hr>
<p><strong>Student ID:</strong> <?=e($student['student_number'])?></p>
<p><strong>Name:</strong> <?=e($student['first_name'].' '.$student['middle_name'].' '.$student['last_name'])?></p>
<p><strong>Program:</strong> <?=e($student['program_name'])?></p>
<p><strong>Academic Year:</strong> <?=e($reg['academic_year'])?> |
<strong>Semester:</strong> <?=e($reg['semester_name'])?></p>
<table class="table table-bordered"><thead><tr><th>#</th><th>Code</th><th>Course</th><th>Credit</th></tr></thead><tbody>
<?php $total=0; foreach($details as $i=>$d): $total+=(float)$d['credit_hour']; ?>
<tr><td><?=$i+1?></td><td><?=e($d['course_code'])?></td><td><?=e($d['course_name'])?></td><td><?=e((string)$d['credit_hour'])?></td></tr>
<?php endforeach; ?>
<tr><th colspan="3">Total Credit Hours</th><th><?=e((string)$total)?></th></tr>
</tbody></table>
<p><strong>Status:</strong> Approved</p>
</div>
<button class="btn btn-primary mt-3" onclick="window.print()">Print</button>
<?php endif; ?>
<?php include __DIR__.'/../includes/footer.php'; ?>
