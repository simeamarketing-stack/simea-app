<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<!doctype html>
<html lang="vi">
<head><meta charset="UTF-8"><title>Không tìm thấy trang</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>"></head>
<body>
<main class="container">
  <h1>404 — Không tìm thấy trang</h1>
  <p><a href="<?= e(url('/')) ?>">Về trang chủ</a></p>
</main>
</body>
</html>
