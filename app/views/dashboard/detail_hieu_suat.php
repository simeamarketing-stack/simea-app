<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn-secondary btn" href="<?= e(url($backUrl)) ?>">&larr; Về dashboard</a>
</div>

<div class="card">
  <p>Tổng 30 ngày gần nhất (chỉ tính ca đã nhập cả sản lượng lẫn chỉ tiêu):
    <strong>
      <?= formatQty($summary['output'], 'count') ?> / <?= formatQty($summary['target'], 'count') ?> hộp
      <?= $summary['pct'] !== null ? '— ' . round($summary['pct'] * 100) . '%' : '' ?>
    </strong>
  </p>
</div>

<div class="card">
<h3>Từng ca sản xuất</h3>
<?php if (!$reports): ?>
  <p class="hint">Chưa có báo cáo ca nào trong 30 ngày gần nhất.</p>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr>
    <th>Ngày</th><th>Lệnh</th><th>Khách hàng</th><th>Chuyền</th>
    <th>Sản lượng</th><th>Chỉ tiêu</th><th>Đạt</th><th>Nhân sự</th><th>SL/người</th>
  </tr></thead>
  <tbody>
  <?php foreach ($reports as $r):
    $pct = ($r['output_qty'] !== null && $r['target_qty']) ? (int) $r['output_qty'] / (int) $r['target_qty'] : null;
    $perWorker = ($r['output_qty'] !== null && $r['worker_count']) ? (int) $r['output_qty'] / (int) $r['worker_count'] : null;
  ?>
    <tr>
      <td><?= e($r['report_date']) ?></td>
      <td><a href="<?= e(url('/bao-cao-ca/' . $r['id'])) ?>"><?= e($r['order_code']) ?></a></td>
      <td><?= e($r['customer_name']) ?></td>
      <td><?= e($r['line']) ?></td>
      <td><?= displayValue($r['output_qty']) ?></td>
      <td><?= displayValue($r['target_qty']) ?></td>
      <td class="<?= ($pct !== null && $pct < 1) ? 'cell-risk' : '' ?>"><?= $pct !== null ? round($pct * 100) . '%' : displayValue(null) ?></td>
      <td><?= displayValue($r['worker_count']) ?></td>
      <td><?= $perWorker !== null ? formatQty($perWorker) : displayValue(null) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
</div>
