<?php
require_once __DIR__ . '/../config/config.php';
if (is_logged_in()) {
    audit($pdo, 'LOGOUT', 'users', (string)$_SESSION['user_id']);
}
$_SESSION = [];
session_destroy();
header('Location: ' . BASE_URL . '/auth/login.php');
exit;
