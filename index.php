<?php
require_once __DIR__ . '/config/config.php';

if (is_logged_in()) {
    redirect(role_dashboard($_SESSION['role']));
}
redirect('auth/login.php');
