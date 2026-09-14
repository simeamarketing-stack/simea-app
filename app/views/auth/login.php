<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="login-box">
  <h1>Đăng nhập SIMEA</h1>
  <form method="post" action="<?= e(url('/login')) ?>" class="stacked-form">
    <?= Csrf::field() ?>
    <label>Tên đăng nhập
      <input type="text" name="username" required autofocus autocapitalize="none">
    </label>
    <label>Mật khẩu
      <input type="password" name="password" required>
    </label>
    <button type="submit" class="btn-primary">Đăng nhập</button>
  </form>
</div>
