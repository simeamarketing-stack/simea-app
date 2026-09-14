<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1><?= e($material['name']) ?> (<?= e($material['code']) ?>)</h1>
  <a class="btn-secondary btn" href="<?= e(url('/kho/ton-kho')) ?>">&larr; Tồn kho</a>
</div>

<div class="card">
  <p>Tồn kho: <strong><?= e((string) $stock) ?> <?= e($material['unit_of_measure']) ?></strong>
  &nbsp;·&nbsp; Đã giữ: <strong><?= e((string) $reserved) ?></strong>
  &nbsp;·&nbsp; Khả dụng: <strong><?= e((string) $available) ?></strong></p>
</div>

<div class="card">
<h3>Đang được giữ chỗ bởi</h3>
<div class="table-wrap">
<table>
  <thead><tr><th>Lệnh sản xuất</th><th>Số lượng giữ</th><th>Người giữ</th><th>Ngày giữ</th></tr></thead>
  <tbody>
  <?php foreach ($reservations as $r): ?>
    <tr>
      <td><a href="<?= e(url('/lenh-san-xuat/' . $r['production_order_id'])) ?>"><?= e($r['order_code']) ?></a></td>
      <td><?= e((string) $r['quantity_reserved']) ?></td>
      <td><?= e($r['created_by_name']) ?></td>
      <td><?= e($r['created_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$reservations): ?><tr><td colspan="4">Không có phiếu giữ chỗ nào đang hoạt động.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
</div>

<div class="card">
<h3>Lịch sử giao dịch gần đây</h3>
<div class="table-wrap">
<table>
  <thead><tr><th>Thời gian</th><th>Loại</th><th>Số lượng</th><th>Lệnh SX</th><th>Người ghi</th></tr></thead>
  <tbody>
  <?php foreach ($history as $h): ?>
    <tr>
      <td><?= e($h['created_at']) ?></td>
      <td><?= e($h['transaction_type']) ?></td>
      <td><?= e((string) $h['quantity']) ?></td>
      <td><?= e($h['order_code'] ?? '') ?></td>
      <td><?= e($h['created_by_name'] ?? '') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$history): ?><tr><td colspan="5">Chưa có giao dịch nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
</div>
