<?php
require_once __DIR__ . '/../config/config.php';

$message = '';
$error = '';

try {
    $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name='system_admin' LIMIT 1");
    $stmt->execute();
    $roleId = $stmt->fetchColumn();

    if (!$roleId) {
        throw new RuntimeException('Roles are missing. Import database/schema.sql first.');
    }

    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username=?');
    $stmt->execute(['admin']);

    if ($stmt->fetchColumn()) {
        $message = 'Admin account already exists.';
    } else {
        $hash = password_hash('Admin@123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (username,password_hash,role_id,status) VALUES (?,?,?, "active")'
        );
        $stmt->execute(['admin', $hash, $roleId]);
        $message = 'Admin created. Username: admin | Password: Admin@123. Delete/protect this tools folder now.';
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>Setup Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="container py-5">
<h2>System Administrator Setup</h2>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary">Go to Login</a>
</body></html>
