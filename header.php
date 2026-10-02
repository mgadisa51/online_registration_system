<?php
require_once __DIR__ . '/../config/config.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
<div class="container">
<a class="navbar-brand" href="<?= BASE_URL ?>/">Borana University ORS</a>
<div class="ms-auto">
<?php if (is_logged_in()): ?>
<span class="text-white me-3"><?= e($_SESSION['username']) ?> (<?= e($_SESSION['role']) ?>)</span>
<a class="btn btn-light btn-sm" href="<?= BASE_URL ?>/auth/logout.php">Logout</a>
<?php endif; ?>
</div>
</div>
</nav>
<main class="container py-4">
<?php show_flash(); ?>
