<?php
$title = 'Login';
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) redirect(role_dashboard($_SESSION['role']));

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT u.user_id, u.username, u.password_hash, u.status, r.role_name
             FROM users u JOIN roles r ON r.role_id=u.role_id
             WHERE u.username=? LIMIT 1'
        );
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];
            audit($pdo, 'LOGIN', 'users', (string)$user['user_id']);
            redirect(role_dashboard($user['role_name']));
        } else {
            $error = 'Invalid username/password or inactive account.';
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="row justify-content-center">
<div class="col-md-5">
<div class="card p-4">
<h3 class="mb-3">System Login</h3>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="mb-3">
<label class="form-label">Username</label>
<input class="form-control" name="username" required autofocus>
</div>
<div class="mb-3">
<label class="form-label">Password</label>
<input class="form-control" type="password" name="password" required>
</div>
<button class="btn btn-primary w-100">Login</button>
</form>
</div>
</div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
