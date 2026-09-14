<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$typeLabels = ['nhap' => 'Nhập', 'tra' => 'Trả kho', 'hong' => 'Hỏng', 'dieu_chinh' => 'Điều chỉnh'];
?>
<div class="page-head">
  <h1>Phiếu kho</h1>
  <a class="btn" href="<?= e(url('/kho/phieu/tao')) ?>">+ Ghi nhận phiếu</a>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Ngày</th><th>Loại</th><th>Số phiếu/PO</th><th>Người ghi</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($vouchers as $v): ?>
    <tr>
      <td><?= e($v['created_at']) ?></td>
      <td><?= e($typeLabels[$v['voucher_type']] ?? $v['voucher_type']) ?></td>
      <td><?= e($v['voucher_no'] ?? '') ?></td>
      <td><?= e($v['created_by_name']) ?></td>
      <td><a href="<?= e(url('/kho/phieu/' . $v['id'])) ?>">Xem</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$vouchers): ?><tr><td colspan="5">Chưa có phiếu nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
