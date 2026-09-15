<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$statusLabels = ['draft' => 'Nháp', 'released' => 'Đã phát hành', 'in_progress' => 'Đang chạy', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'];
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn-secondary btn" href="<?= e(url($backUrl)) ?>">&larr; Về dashboard</a>
</div>

<?php if (!$rows): ?>
  <div class="card"><p class="hint">Chưa có lệnh nào đã phát hành hoặc đang chạy.</p></div>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr>
    <th>Mã lệnh</th><th>Khách hàng</th><th>SKU</th><th>Kế hoạch</th><th>Đã làm</th>
    <th>Tiến độ</th><th>Chuyền</th><th>Hạn giao</th><th>Trạng thái</th><th>Cảnh báo</th>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $r): $o = $r['order']; ?>
    <tr>
      <td><a href="<?= e(url('/lenh-san-xuat/' . $o['id'])) ?>"><?= e($o['order_code']) ?></a></td>
      <td><?= e($o['customer_name']) ?></td>
      <td><?= e($o['sku_code'] ?? '') ?></td>
      <td><?= formatQty($o['planned_quantity'], 'count') ?></td>
      <td><?= formatQty($r['output'], 'count') ?></td>
      <td>
        <?php if ($r['pct_done'] !== null): ?>
          <?= round($r['pct_done'] * 100) ?>%
          <?php if ($r['pct_time'] !== null): ?>
            <span class="hint">(đã qua <?= round($r['pct_time'] * 100) ?>% thời gian)</span>
            <?php if ($r['pct_done'] < $r['pct_time'] - 0.05): ?>
              <span class="badge badge-risk">Chậm</span>
            <?php endif; ?>
          <?php endif; ?>
        <?php else: ?>
          <?= displayValue(null) ?>
        <?php endif; ?>
      </td>
      <td><?= displayValue($o['chuyen']) ?></td>
      <td><?= displayValue($o['current_due_date']) ?></td>
      <td><?= e($statusLabels[$o['status']] ?? $o['status']) ?></td>
      <td><span class="badge <?= e($r['badge']['css']) ?>"><?= e($r['badge']['label']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
