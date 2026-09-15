<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIMEA · Quản lý sản xuất</title>
<link rel="icon" href="<?= e(url('/assets/img/logo-mark.svg')) ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
</head>
<body>
<?php if (Auth::check()): ?>
<?php require APP_PATH . '/views/layout/nav.php'; ?>
<?php endif; ?>
<main class="container">
<?php require APP_PATH . '/views/layout/flash.php'; ?>
