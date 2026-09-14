<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>Tồn kho vật tư</h1>
  <?php if (Auth::is(ROLE_KHO)): ?>
    <div>
      <a class="btn-secondary btn" href="<?= e(url('/kho/phieu')) ?>">Danh sách phiếu</a>
      <a class="btn" href="<?= e(url('/kho/phieu/tao')) ?>">+ Ghi nhận phiếu</a>
    </div>
  <?php endif; ?>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Vật tư</th><th>Nhóm</th><th>Tồn kho</th><th>Đã giữ</th><th>Khả dụng</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($summary as $m):
    $available = (float) $m['stock'] - (float) $m['reserved'];
    $displayStock = $m['unit_type'] === 'count' ? (int) roundQuantity((float) $m['stock'], 'count') : $m['stock'];
    $displayAvailable = $m['unit_type'] === 'count' ? (int) roundQuantity($available, 'count') : $available;
  ?>
    <tr class="<?= $available < 0 ? 'row-risk' : '' ?>">
      <td><?= e($m['name']) ?> (<?= e($m['code']) ?>)</td>
      <td><?= e($m['group_name']) ?></td>
      <td><?= e((string) $displayStock) ?> <?= e($m['unit_of_measure']) ?></td>
      <td><?= e((string) $m['reserved']) ?> <?= e($m['unit_of_measure']) ?></td>
      <td><?= e((string) $displayAvailable) ?> <?= e($m['unit_of_measure']) ?></td>
      <td><a href="<?= e(url('/kho/vat-tu/' . $m['id'])) ?>">Chi tiết</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$summary): ?><tr><td colspan="6">Chưa có vật tư nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
