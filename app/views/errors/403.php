<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<!doctype html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Không có quyền truy cập</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>"></head>
<body>
<main class="container">
  <h1>403 — Không có quyền truy cập</h1>
  <p>Tài khoản của bạn không có quyền xem trang này.</p>
  <p><a href="<?= e(url('/')) ?>">Về trang chủ</a></p>
</main>
</body>
</html>
