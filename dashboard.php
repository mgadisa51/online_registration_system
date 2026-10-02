<?php
require_once __DIR__ . '/../config/config.php';
require_role('student');
$student = current_student($pdo);
$title = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<h2>Student Dashboard</h2>
<?php if (!$student): ?>
<div class="alert alert-danger">Student profile is not linked to this account.</div>
<?php else: ?>
<div class="card p-4 mb-4">
<h5><?= e($student['first_name'].' '.$student['last_name']) ?></h5>
<p class="mb-1"><strong>Student ID:</strong> <?= e($student['student_number']) ?></p>
<p class="mb-1"><strong>Program:</strong> <?= e($student['program_name']) ?></p>
<p><strong>Year Level:</strong> <?= e((string)$student['year_level']) ?></p>
</div>
<div class="row g-3">
<?php
$links = [
 ['Course Registration','student/registration.php'],
 ['Registration History','student/history.php'],
 ['Registration Status','student/status.php'],
 ['Registration Slip','student/slip.php']
];
foreach ($links as [$name,$url]): ?>
<div class="col-md-3"><a class="text-decoration-none" href="<?= BASE_URL.'/'.$url ?>">
<div class="card p-4 dashboard-card"><h5><?= e($name) ?></h5><span>Open</span></div>
</a></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
