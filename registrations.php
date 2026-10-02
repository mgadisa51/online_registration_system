<?php
require_once __DIR__ . '/../config/config.php';
require_role('advisor');
$title='Advisor Registrations';
$stmt=$pdo->prepare(
'SELECT r.registration_id,r.registration_date,r.status,
        s.student_number,CONCAT(s.first_name," ",s.last_name) student_name,
        y.academic_year,sem.semester_name
 FROM registrations r
 JOIN students s ON s.student_id=r.student_id
 JOIN academic_years y ON y.academic_year_id=r.academic_year_id
 JOIN semesters sem ON sem.semester_id=r.semester_id
 JOIN advisors a ON a.advisor_id=r.advisor_id
 WHERE a.user_id=? ORDER BY r.registration_id DESC'
);
$stmt->execute([$_SESSION['user_id']]); $rows=$stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Student Registrations</h2>
<div class="card p-4"><table class="table">
<thead><tr><th>Student</th><th>Year/Semester</th><th>Date</th><th>Status</th><th></th></tr></thead>
<tbody><?php foreach($rows as $r): ?><tr>
<td><?=e($r['student_number'].' - '.$r['student_name'])?></td>
<td><?=e($r['academic_year'].' / '.$r['semester_name'])?></td>
<td><?=e($r['registration_date'])?></td><td><?=e($r['status'])?></td>
<td><a class="btn btn-sm btn-outline-primary" href="<?=BASE_URL?>/advisor/review.php?id=<?=$r['registration_id']?>">Review</a></td>
</tr><?php endforeach; ?></tbody></table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
