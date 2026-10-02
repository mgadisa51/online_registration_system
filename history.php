<?php
require_once __DIR__ . '/../config/config.php';
require_role('student');
$student=current_student($pdo); $title='Registration History';
$stmt=$pdo->prepare(
'SELECT r.registration_id,y.academic_year,s.semester_name,r.registration_date,r.status,
        COALESCE(SUM(rd.credit_hour),0) total_credit
 FROM registrations r
 JOIN academic_years y ON y.academic_year_id=r.academic_year_id
 JOIN semesters s ON s.semester_id=r.semester_id
 LEFT JOIN registration_details rd ON rd.registration_id=r.registration_id
 WHERE r.student_id=? GROUP BY r.registration_id ORDER BY r.registration_id DESC'
);
$stmt->execute([$student['student_id']]); $rows=$stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Registration History</h2>
<div class="card p-4"><table class="table table-striped">
<thead><tr><th>Year</th><th>Semester</th><th>Date</th><th>Credits</th><th>Status</th></tr></thead>
<tbody><?php foreach($rows as $r): ?><tr>
<td><?=e($r['academic_year'])?></td><td><?=e($r['semester_name'])?></td>
<td><?=e($r['registration_date'])?></td><td><?=e((string)$r['total_credit'])?></td>
<td><?=e($r['status'])?></td>
</tr><?php endforeach; ?></tbody></table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
