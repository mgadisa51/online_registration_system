<?php
require_once __DIR__ . '/../config/config.php';
require_role('student');

$student = current_student($pdo);
if (!$student) exit('Student profile not found.');

$title = 'Course Registration';
$errors = [];
$selected = [];

$periodStmt = $pdo->query(
    "SELECT s.semester_id, s.semester_name, s.registration_open, s.registration_close,
            y.academic_year_id, y.academic_year
     FROM semesters s JOIN academic_years y ON y.academic_year_id=s.academic_year_id
     WHERE s.status='active' AND y.status='active'
     ORDER BY y.academic_year_id DESC, s.semester_id DESC"
);
$periods = $periodStmt->fetchAll();
$period = $periods[0] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $semesterId = (int)($_POST['semester_id'] ?? 0);
    $courseIds = array_values(array_unique(array_map('intval', $_POST['courses'] ?? [])));

    if (!$period || $semesterId !== (int)$period['semester_id']) {
        $errors[] = 'Invalid academic period.';
    } elseif (!registration_period_open($pdo, $semesterId)) {
        $errors[] = 'Registration is not currently open.';
    } elseif ($student['status'] !== 'active') {
        $errors[] = 'Only active students may register.';
    } elseif (!$courseIds) {
        $errors[] = 'Select at least one course.';
    } else {
        foreach ($courseIds as $courseId) {
            $errors = array_merge($errors, validate_course_selection($pdo, (int)$student['student_id'], $courseId, $semesterId));
        }

        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = $pdo->prepare("SELECT course_id, course_code, course_name, credit_hour FROM courses WHERE course_id IN ($placeholders)");
        $stmt->execute($courseIds);
        $courses = $stmt->fetchAll();

        $totalCredits = array_sum(array_map(fn($c) => (float)$c['credit_hour'], $courses));

        $rule = $pdo->prepare('SELECT min_credit, max_credit FROM registration_rules WHERE semester_id=?');
        $rule->execute([$semesterId]);
        $rules = $rule->fetch();

        if ($rules) {
            if ($rules['min_credit'] !== null && $totalCredits < (float)$rules['min_credit']) {
                $errors[] = 'Selected credits are below the configured minimum.';
            }
            if ($rules['max_credit'] !== null && $totalCredits > (float)$rules['max_credit']) {
                $errors[] = 'Selected credits exceed the configured maximum.';
            }
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO registrations
                     (student_id, academic_year_id, semester_id, status)
                     VALUES (?, ?, ?, "pending")
                     ON DUPLICATE KEY UPDATE
                       registration_date=CURRENT_TIMESTAMP,
                       status="pending",
                       advisor_comment=NULL,
                       approved_at=NULL'
                );
                $stmt->execute([$student['student_id'], $period['academic_year_id'], $semesterId]);

                $stmt = $pdo->prepare(
                    'SELECT registration_id FROM registrations
                     WHERE student_id=? AND academic_year_id=? AND semester_id=?'
                );
                $stmt->execute([$student['student_id'], $period['academic_year_id'], $semesterId]);
                $registrationId = (int)$stmt->fetchColumn();

                $pdo->prepare('DELETE FROM registration_details WHERE registration_id=?')
                    ->execute([$registrationId]);

                $detail = $pdo->prepare(
                    'INSERT INTO registration_details (registration_id, course_id, credit_hour, status)
                     VALUES (?, ?, ?, "selected")'
                );
                foreach ($courses as $course) {
                    $detail->execute([$registrationId, $course['course_id'], $course['credit_hour']]);
                }

                audit($pdo, 'REGISTRATION_SUBMITTED', 'registrations', (string)$registrationId);
                $pdo->commit();
                flash('success', 'Registration submitted successfully.');
                redirect('student/status.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'Registration could not be saved.';
            }
        }
    }
}

$courseStmt = $pdo->prepare(
    'SELECT c.course_id, c.course_code, c.course_name, c.credit_hour, cu.course_type
     FROM curriculum cu
     JOIN courses c ON c.course_id=cu.course_id
     WHERE cu.program_id=? AND cu.year_level=? AND cu.status="active"
       AND cu.semester_name=(
         SELECT semester_name FROM semesters WHERE semester_id=?
       )
       AND c.status="active"
     ORDER BY c.course_code'
);
if ($period) {
    $courseStmt->execute([$student['program_id'], $student['year_level'], $period['semester_id']]);
    $availableCourses = $courseStmt->fetchAll();
} else {
    $availableCourses = [];
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>Course Registration</h2>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>

<?php if (!$period): ?>
<div class="alert alert-warning">No active academic registration period is configured.</div>
<?php else: ?>
<div class="card p-4">
<p><strong>Academic Year:</strong> <?= e($period['academic_year']) ?>
&nbsp; | &nbsp; <strong>Semester:</strong> <?= e($period['semester_name']) ?></p>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="semester_id" value="<?= e((string)$period['semester_id']) ?>">
<table class="table table-bordered">
<thead><tr><th>Select</th><th>Code</th><th>Course</th><th>Credit</th><th>Type</th></tr></thead>
<tbody>
<?php foreach ($availableCourses as $course): ?>
<tr>
<td><input class="course-check" type="checkbox" name="courses[]" value="<?= e((string)$course['course_id']) ?>" data-credit="<?= e((string)$course['credit_hour']) ?>"></td>
<td><?= e($course['course_code']) ?></td>
<td><?= e($course['course_name']) ?></td>
<td><?= e((string)$course['credit_hour']) ?></td>
<td><?= e($course['course_type']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<p><strong>Total selected credit hours:</strong> <span id="totalCredits">0</span></p>
<button class="btn btn-primary" <?= !$availableCourses ? 'disabled' : '' ?>>Submit Registration</button>
</form>
</div>
<?php endif; ?>
<script>
document.querySelectorAll('.course-check').forEach(c => c.addEventListener('change', () => {
 let total=0;
 document.querySelectorAll('.course-check:checked').forEach(x => total += parseFloat(x.dataset.credit||0));
 document.getElementById('totalCredits').textContent = total.toFixed(1);
}));
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
