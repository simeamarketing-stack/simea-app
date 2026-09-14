<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>Loại cà phê</h1>
  <?php if (Auth::is(ROLE_QUAN_LY)): ?>
    <a class="btn" href="<?= e(url('/danh-muc/loai-ca-phe/tao')) ?>">+ Thêm loại cà phê</a>
  <?php endif; ?>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Mã</th><th>Tên</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($coffeeTypes as $t): ?>
    <tr>
      <td><?= e($t['code']) ?></td>
      <td><?= e($t['name']) ?></td>
      <td><?php if (Auth::is(ROLE_QUAN_LY)): ?><a href="<?= e(url('/danh-muc/loai-ca-phe/' . $t['id'] . '/sua')) ?>">Sửa</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$coffeeTypes): ?><tr><td colspan="3">Chưa có loại cà phê nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
