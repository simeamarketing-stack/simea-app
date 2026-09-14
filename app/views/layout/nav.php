<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$role = Auth::role();
$user = Auth::user();
?>
<header class="topbar">
  <div class="brand"><a href="<?= e(url('/')) ?>">SIMEA</a></div>
  <nav class="mainnav">
    <?php if ($role === ROLE_DIEU_PHOI): ?>
      <a href="<?= e(url('/danh-muc/khach-hang')) ?>">Danh mục</a>
      <a href="<?= e(url('/bom')) ?>">BOM &amp; định mức</a>
    <?php elseif ($role === ROLE_KHO): ?>
      <a href="<?= e(url('/danh-muc/vat-tu')) ?>">Vật tư</a>
    <?php endif; ?>
    <a href="<?= e(url('/kho/ton-kho')) ?>">Kho vật tư</a>
    <a href="<?= e(url('/lenh-san-xuat')) ?>">Lệnh sản xuất</a>
    <a href="<?= e(url('/bao-cao-ca')) ?>">Báo cáo ca</a>
  </nav>
  <div class="userbox">
    <span><?= e($user['full_name'] ?? '') ?> · <?= e(ROLE_LABELS[$role] ?? $role) ?></span>
    <form method="post" action="<?= e(url('/logout')) ?>">
      <?= Csrf::field() ?>
      <button type="submit" class="link-button">Đăng xuất</button>
    </form>
  </div>
</header>
