<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>Khách hàng</h1>
  <?php if (Auth::is(ROLE_QUAN_LY)): ?>
    <a class="btn" href="<?= e(url('/danh-muc/khach-hang/tao')) ?>">+ Thêm khách hàng</a>
  <?php endif; ?>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Mã</th><th>Tên khách hàng</th><th>Thị trường</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($customers as $c): ?>
    <tr>
      <td><?= e($c['code']) ?></td>
      <td><?= e($c['name']) ?></td>
      <td><?= e($c['market'] ?? '') ?></td>
      <td><?php if (Auth::is(ROLE_QUAN_LY)): ?><a href="<?= e(url('/danh-muc/khach-hang/' . $c['id'] . '/sua')) ?>">Sửa</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$customers): ?><tr><td colspan="4">Chưa có khách hàng nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
