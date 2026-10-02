<?php
require_once __DIR__ . '/../config/config.php';
require_role('student');
$student = current_student($pdo);
$title='Registration Status';
$stmt=$pdo->prepare(
'SELECT r.*, y.academic_year, s.semester_name,
        CONCAT(a.first_name," ",a.last_name) AS advisor_name
 FROM registrations r
 JOIN academic_years y ON y.academic_year_id=r.academic_year_id
 JOIN semesters s ON s.semester_id=r.semester_id
 LEFT JOIN advisors av ON av.advisor_id=r.advisor_id
 LEFT JOIN users u ON u.user_id=av.user_id
 LEFT JOIN students a ON a.user_id=u.user_id
 WHERE r.student_id=? ORDER BY r.registration_id DESC LIMIT 5'
);
$stmt->execute([$student['student_id']]);
$rows=$stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<h2>Registration Status</h2>
<div class="card p-4"><table class="table">
<thead><tr><th>Year</th><th>Semester</th><th>Status</th><th>Advisor Comment</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?><tr>
<td><?=e($r['academic_year'])?></td><td><?=e($r['semester_name'])?></td>
<td><span class="badge bg-secondary"><?=e($r['status'])?></span></td>
<td><?=e($r['advisor_comment'])?></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__.'/../includes/footer.php'; ?>
