<?php
declare(strict_types=1);

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function is_logged_in(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect('auth/login.php');
    }
}

function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): void {
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid security token.');
    }
}

function audit(PDO $pdo, string $action, ?string $table = null, ?string $recordId = null, ?string $details = null): void {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_logs (user_id, action, table_name, record_id, details)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$_SESSION['user_id'] ?? null, $action, $table, $recordId, $details]);
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function show_flash(): void {
    foreach ($_SESSION['flash'] ?? [] as $item) {
        echo '<div class="alert alert-' . e($item['type']) . '">' . e($item['message']) . '</div>';
    }
    unset($_SESSION['flash']);
}

function current_student(PDO $pdo): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare(
        'SELECT s.*, d.department_name, p.program_name, p.program_code
         FROM students s
         JOIN departments d ON d.department_id=s.department_id
         JOIN programs p ON p.program_id=s.program_id
         WHERE s.user_id=? LIMIT 1'
    );
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function role_dashboard(string $role): string {
    return match ($role) {
        'student' => 'student/dashboard.php',
        'advisor' => 'advisor/dashboard.php',
        'department_admin' => 'admin/dashboard.php',
        'registrar' => 'registrar/dashboard.php',
        'system_admin' => 'admin/dashboard.php',
        default => 'auth/login.php',
    };
}

function registration_period_open(PDO $pdo, int $semesterId): bool {
    $stmt = $pdo->prepare(
        'SELECT registration_open, registration_close
         FROM semesters WHERE semester_id=? AND status="active"'
    );
    $stmt->execute([$semesterId]);
    $s = $stmt->fetch();
    if (!$s || !$s['registration_open'] || !$s['registration_close']) return false;
    $now = new DateTime();
    return $now >= new DateTime($s['registration_open']) &&
           $now <= new DateTime($s['registration_close']);
}

function validate_course_selection(PDO $pdo, int $studentId, int $courseId, int $semesterId): array {
    $errors = [];

    $stmt = $pdo->prepare(
        'SELECT c.course_id, c.course_code, c.course_name, c.credit_hour
         FROM courses c WHERE c.course_id=? AND c.status="active"'
    );
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        return ['Course not found.'];
    }

    $stmt = $pdo->prepare(
        'SELECT s.program_id, s.year_level
         FROM students s WHERE s.student_id=? AND s.status="active"'
    );
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();

    if (!$student) return ['Student is not active or does not exist.'];

    $stmt = $pdo->prepare(
        'SELECT 1 FROM curriculum
         WHERE program_id=? AND course_id=? AND year_level=?
           AND status="active" AND semester_name=(
               SELECT semester_name FROM semesters WHERE semester_id=?
           ) LIMIT 1'
    );
    $stmt->execute([$student['program_id'], $courseId, $student['year_level'], $semesterId]);
    if (!$stmt->fetchColumn()) {
        $errors[] = 'Course is not part of the student curriculum for this academic level/semester.';
    }

    $stmt = $pdo->prepare(
        'SELECT p.prerequisite_course_id, p.minimum_grade, c.course_code
         FROM prerequisites p
         JOIN courses c ON c.course_id=p.prerequisite_course_id
         WHERE p.course_id=?'
    );
    $stmt->execute([$courseId]);
    foreach ($stmt->fetchAll() as $pre) {
        $r = $pdo->prepare(
            'SELECT result_status, grade, grade_point
             FROM student_course_results
             WHERE student_id=? AND course_id=?
             ORDER BY recorded_at DESC LIMIT 1'
        );
        $r->execute([$studentId, $pre['prerequisite_course_id']]);
        $result = $r->fetch();

        if (!$result || $result['result_status'] !== 'passed') {
            $errors[] = 'Prerequisite not satisfied: ' . $pre['course_code'] . '.';
        }
        // Exact grade policy is university-configurable. The system only enforces
        // minimum_grade here when a numeric grade point is available.
        if ($result && $pre['minimum_grade'] && $result['grade_point'] !== null) {
            // Grade thresholds must be configured from official rules before
            // enforcing letter-grade comparisons. This starter does not guess them.
        }
    }

    return $errors;
}
