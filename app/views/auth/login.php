<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="login-box">
  <div class="login-brand">
    <img class="brand-mark" src="<?= e(url('/assets/img/logo-mark.svg')) ?>" alt="">
    <span class="brand-word">IMEA</span>
  </div>
  <p class="login-tagline">Hệ thống quản lý vận hành sản xuất</p>

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
