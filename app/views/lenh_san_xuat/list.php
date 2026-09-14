<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$statusLabels = ['draft' => 'Nháp', 'released' => 'Đã phát hành', 'in_progress' => 'Đang chạy', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'];
?>
<div class="page-head">
  <h1>Lệnh sản xuất</h1>
  <?php if (Auth::is(ROLE_DIEU_PHOI)): ?>
    <a class="btn" href="<?= e(url('/lenh-san-xuat/tao')) ?>">+ Tạo lệnh</a>
  <?php endif; ?>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Mã lệnh</th><th>Khách hàng</th><th>SKU</th><th>SL kế hoạch</th><th>Trạng thái</th><th>Hạn giao hiện hành</th><th>Cảnh báo</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><a href="<?= e(url('/lenh-san-xuat/' . $o['id'])) ?>"><?= e($o['order_code']) ?></a></td>
      <td><?= e($o['customer_name']) ?></td>
      <td><?= e($o['sku_code'] ?? '') ?></td>
      <td><?= displayValue($o['planned_quantity']) ?></td>
      <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
      <td><?= displayValue($o['current_due_date']) ?></td>
      <td><span class="badge <?= e($o['badge']['css']) ?>"><?= e($o['badge']['label']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$orders): ?><tr><td colspan="7">Chưa có lệnh sản xuất nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
