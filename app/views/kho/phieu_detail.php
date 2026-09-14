<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$typeLabels = ['nhap' => 'Nhập', 'tra' => 'Trả kho', 'hong' => 'Hỏng', 'dieu_chinh' => 'Điều chỉnh'];
?>
<div class="page-head">
  <h1>Phiếu #<?= (int) $voucher['id'] ?> — <?= e($typeLabels[$voucher['voucher_type']] ?? $voucher['voucher_type']) ?></h1>
  <a class="btn-secondary btn" href="<?= e(url('/kho/phieu')) ?>">&larr; Danh sách phiếu</a>
</div>
<div class="card">
  <p>Số phiếu/PO: <?= e($voucher['voucher_no'] ?? '') ?></p>
  <p>Ghi chú: <?= e($voucher['note'] ?? '') ?></p>
  <p>Người ghi: <?= e($voucher['created_by_name']) ?> · <?= e($voucher['created_at']) ?></p>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Vật tư</th><th>Số lượng</th></tr></thead>
  <tbody>
  <?php foreach ($lines as $l): ?>
    <tr>
      <td><?= e($l['material_name']) ?> (<?= e($l['material_code']) ?>)</td>
      <td><?= e((string) $l['quantity']) ?> <?= e($l['unit_of_measure']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
