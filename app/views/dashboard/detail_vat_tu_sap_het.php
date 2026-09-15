<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn-secondary btn" href="<?= e(url($backUrl)) ?>">&larr; Về dashboard</a>
</div>

<?php if (!$alerts): ?>
  <div class="card"><p class="hint">Không có vật tư nào dưới ngưỡng cảnh báo hoặc thiếu so với lệnh đang chạy.</p></div>
<?php endif; ?>

<?php foreach ($alerts as $a):
  $m = $a['material'];
  $unit = $m['unit_of_measure'];
  $type = $m['unit_type'];
?>
<div class="card">
  <div class="page-head">
    <h3><?= e($m['name']) ?> <span class="hint">(<?= e($m['code']) ?>)</span></h3>
    <a class="btn-secondary btn" href="<?= e(url('/kho/vat-tu/' . $m['id'])) ?>">Xem trong kho</a>
  </div>

  <p>
    <?php foreach ($a['reasons'] as $reason): ?>
      <span class="badge badge-risk"><?= e($reason) ?></span>
    <?php endforeach; ?>
  </p>

  <div class="table-wrap">
  <table>
    <thead><tr>
      <th>Tồn khả dụng</th><th>Ngưỡng cảnh báo</th><th>Nhu cầu còn thiếu (lệnh đang chạy)</th>
      <th>Cần mua thêm</th><th>Để về ngưỡng an toàn</th>
    </tr></thead>
    <tbody>
      <tr>
        <td class="<?= $a['available'] < 0 ? 'cell-risk' : '' ?>"><?= formatQty($a['available'], $type) ?> <?= e($unit) ?></td>
        <td><?= $m['min_stock_alert'] !== null ? formatQty($m['min_stock_alert'], $type) . ' ' . e($unit) : displayValue(null) ?></td>
        <td><?= formatQty($a['outstanding'], $type) ?> <?= e($unit) ?></td>
        <td class="<?= $a['need_to_buy'] > 0 ? 'cell-risk' : '' ?>"><strong><?= formatQty($a['need_to_buy'], $type) ?> <?= e($unit) ?></strong></td>
        <td><?= $a['to_safe_level'] !== null ? formatQty($a['to_safe_level'], $type) . ' ' . e($unit) : displayValue(null) ?></td>
      </tr>
    </tbody>
  </table>
  </div>

  <?php if ($a['orders']): ?>
    <h4>Đang thiếu cho các lệnh</h4>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Mã lệnh</th><th>Khách hàng</th><th>Hạn giao</th><th>Cần</th><th>Đã giữ chỗ</th><th>Còn thiếu</th></tr></thead>
      <tbody>
      <?php foreach ($a['orders'] as $o): ?>
        <tr>
          <td><a href="<?= e(url('/lenh-san-xuat/' . $o['order_id'])) ?>"><?= e($o['order_code']) ?></a></td>
          <td><?= e($o['customer_name']) ?></td>
          <td><?= displayValue($o['current_due_date']) ?></td>
          <td><?= formatQty($o['required'], $type) ?></td>
          <td><?= formatQty($o['reserved'], $type) ?></td>
          <td class="cell-risk"><?= formatQty($o['shortfall'], $type) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php else: ?>
    <p class="hint">Chưa có lệnh đang chạy nào thiếu vật tư này — cảnh báo đến từ ngưỡng tồn kho.</p>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="card">
<h3>Vật tư hỏng / hao hụt tháng này</h3>
<?php if (!$damaged): ?>
  <p class="hint">Chưa ghi nhận vật tư hỏng/hao hụt trong tháng này.</p>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr><th>Vật tư</th><th>Số lượng</th></tr></thead>
  <tbody>
  <?php foreach ($damaged as $d): ?>
    <tr><td><?= e($d['name']) ?></td><td><?= formatQty($d['damaged_qty']) ?> <?= e($d['unit_of_measure']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
</div>
