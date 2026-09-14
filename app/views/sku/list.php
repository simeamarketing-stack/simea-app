<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>SKU sản phẩm</h1>
  <?php if (Auth::is(ROLE_DIEU_PHOI)): ?>
    <a class="btn" href="<?= e(url('/danh-muc/sku/tao')) ?>">+ Thêm SKU</a>
  <?php endif; ?>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Mã SKU</th><th>Khách hàng</th><th>Loại cà phê</th><th>Số viên/hộp</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($skus as $s): ?>
    <tr>
      <td><?= e($s['code']) ?></td>
      <td><?= e($s['customer_name']) ?></td>
      <td><?= e($s['coffee_type_name']) ?></td>
      <td><?= (int) $s['units_per_box'] ?></td>
      <td>
        <a href="<?= e(url('/bom/sku/' . $s['id'])) ?>">BOM</a>
        <?php if (Auth::is(ROLE_DIEU_PHOI)): ?> · <a href="<?= e(url('/danh-muc/sku/' . $s['id'] . '/sua')) ?>">Sửa</a><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$skus): ?><tr><td colspan="5">Chưa có SKU nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
