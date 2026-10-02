<?php
require_once __DIR__ . '/../config/config.php';

require_role('department_admin', 'system_admin');

$title = 'Curriculum Management';

/*
|--------------------------------------------------------------------------
| Add curriculum item
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf($_POST['csrf_token'] ?? null);

    $program_id = (int)($_POST['program_id'] ?? 0);
    $course_id = (int)($_POST['course_id'] ?? 0);
    $year_level = (int)($_POST['year_level'] ?? 0);
    $semester_name = trim($_POST['semester_name'] ?? '');
    $course_type = $_POST['course_type'] ?? 'required';

    if (
        $program_id <= 0 ||
        $course_id <= 0 ||
        $year_level <= 0 ||
        $semester_name === ''
    ) {
        flash('danger', 'Please complete all required fields.');
        redirect('admin/curriculum.php');
    }

    if (!in_array($course_type, ['required', 'elective'], true)) {
        flash('danger', 'Invalid course type.');
        redirect('admin/curriculum.php');
    }

    try {

        $stmt = $pdo->prepare(
            'INSERT INTO curriculum
            (program_id, course_id, year_level, semester_name, course_type)
            VALUES (?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $program_id,
            $course_id,
            $year_level,
            $semester_name,
            $course_type
        ]);

        $id = $pdo->lastInsertId();

        audit(
            $pdo,
            'CREATE',
            'curriculum',
            (string)$id
        );

        flash('success', 'Course successfully added to curriculum.');

    } catch (PDOException $e) {

        if ($e->getCode() === '23000') {
            flash(
                'danger',
                'This course is already assigned to this program, year and semester.'
            );
        } else {
            flash(
                'danger',
                'Could not add the course to the curriculum.'
            );
        }
    }

    redirect('admin/curriculum.php');
}


/*
|--------------------------------------------------------------------------
| Load programs
|--------------------------------------------------------------------------
*/

$programs = $pdo->query(
    'SELECT
        p.program_id,
        p.program_name,
        p.program_code,
        d.department_name
     FROM programs p
     JOIN departments d
       ON d.department_id = p.department_id
     WHERE p.status = "active"
     ORDER BY p.program_name'
)->fetchAll();


/*
|--------------------------------------------------------------------------
| Load courses
|--------------------------------------------------------------------------
*/

$courses = $pdo->query(
    'SELECT
        c.course_id,
        c.course_code,
        c.course_name,
        c.credit_hour,
        d.department_name
     FROM courses c
     JOIN departments d
       ON d.department_id = c.department_id
     WHERE c.status = "active"
     ORDER BY c.course_code'
)->fetchAll();


/*
|--------------------------------------------------------------------------
| Load curriculum
|--------------------------------------------------------------------------
*/

$rows = $pdo->query(
    'SELECT
        cu.curriculum_id,
        p.program_code,
        p.program_name,
        c.course_code,
        c.course_name,
        c.credit_hour,
        cu.year_level,
        cu.semester_name,
        cu.course_type,
        cu.status
     FROM curriculum cu
     JOIN programs p
       ON p.program_id = cu.program_id
     JOIN courses c
       ON c.course_id = cu.course_id
     ORDER BY
        p.program_code,
        cu.year_level,
        cu.semester_name,
        c.course_code'
)->fetchAll();


include __DIR__ . '/../includes/header.php';
?>

<h2 class="mb-3">Curriculum Management</h2>

<div class="card p-3 mb-4">

    <h5>Add Course to Curriculum</h5>

    <form method="post">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <div class="row g-3">

            <!-- Program -->
            <div class="col-md-4">

                <label class="form-label">
                    Program
                </label>

                <select
                    name="program_id"
                    class="form-select"
                    required
                >

                    <option value="">
                        Select Program
                    </option>

                    <?php foreach ($programs as $program): ?>

                        <option value="<?= $program['program_id'] ?>">

                            <?= e($program['program_code']) ?>
                            -
                            <?= e($program['program_name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Course -->
            <div class="col-md-4">

                <label class="form-label">
                    Course
                </label>

                <select
                    name="course_id"
                    class="form-select"
                    required
                >

                    <option value="">
                        Select Course
                    </option>

                    <?php foreach ($courses as $course): ?>

                        <option value="<?= $course['course_id'] ?>">

                            <?= e($course['course_code']) ?>
                            -
                            <?= e($course['course_name']) ?>
                            (<?= $course['credit_hour'] ?> CH)

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Year -->
            <div class="col-md-2">

                <label class="form-label">
                    Year Level
                </label>

                <select
                    name="year_level"
                    class="form-select"
                    required
                >

                    <option value="">Select</option>

                    <?php for ($i = 1; $i <= 8; $i++): ?>

                        <option value="<?= $i ?>">
                            Year <?= $i ?>
                        </option>

                    <?php endfor; ?>

                </select>

            </div>


            <!-- Semester -->
            <div class="col-md-2">

                <label class="form-label">
                    Semester
                </label>

                <select
                    name="semester_name"
                    class="form-select"
                    required
                >

                    <option value="">
                        Select
                    </option>

                    <option value="Semester I">
                        Semester I
                    </option>

                    <option value="Semester II">
                        Semester II
                    </option>

                </select>

            </div>


            <!-- Course type -->
            <div class="col-md-3">

                <label class="form-label">
                    Course Type
                </label>

                <select
                    name="course_type"
                    class="form-select"
                >

                    <option value="required">
                        Required
                    </option>

                    <option value="elective">
                        Elective
                    </option>

                </select>

            </div>


            <div class="col-md-3 d-flex align-items-end">

                <button
                    type="submit"
                    class="btn btn-primary w-100"
                >
                    Add to Curriculum
                </button>

            </div>

        </div>

    </form>

</div>


<div class="card p-3">

    <h5>Current Curriculum</h5>

    <div class="table-responsive">

        <table class="table table-bordered table-striped">

            <thead>

                <tr>

                    <th>Program</th>
                    <th>Course</th>
                    <th>Credit</th>
                    <th>Year</th>
                    <th>Semester</th>
                    <th>Type</th>
                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                <?php if (!$rows): ?>

                    <tr>

                        <td colspan="7" class="text-center">

                            No curriculum records found.

                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($rows as $row): ?>

                        <tr>

                            <td>
                                <?= e($row['program_code']) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= e($row['course_code']) ?>
                                </strong>
                                -
                                <?= e($row['course_name']) ?>
                            </td>

                            <td>
                                <?= e((string)$row['credit_hour']) ?>
                            </td>

                            <td>
                                Year <?= e((string)$row['year_level']) ?>
                            </td>

                            <td>
                                <?= e($row['semester_name']) ?>
                            </td>

                            <td>
                                <?= e(ucfirst($row['course_type'])) ?>
                            </td>

                            <td>
                                <?= e(ucfirst($row['status'])) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>