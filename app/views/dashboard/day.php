<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$weekdays = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
$ts = strtotime($date);
$weekday = $weekdays[(int) date('N', $ts)];
$prettyDate = date('d/m/Y', $ts);
$prevDay = date('Y-m-d', strtotime('-1 day', $ts));
$nextDay = date('Y-m-d', strtotime('+1 day', $ts));

$dayPct = $totals['target'] > 0 ? $totals['output'] / $totals['target'] : null;
$defectRate = $totals['checked'] > 0 ? $totals['defect'] / $totals['checked'] : null;
?>
<div class="page-head">
  <h1><?= e($weekday) ?>, <?= e($prettyDate) ?></h1>
  <div>
    <a class="btn-secondary btn" href="<?= e(url('/dashboard/ngay/' . $prevDay)) ?>">&larr; Hôm trước</a>
    <a class="btn-secondary btn" href="<?= e(url('/dashboard/ngay/' . $nextDay)) ?>">Hôm sau &rarr;</a>
    <a class="btn-secondary btn" href="<?= e(url($backUrl)) ?>">Về dashboard</a>
  </div>
</div>

<div class="kpi-row">
  <div class="kpi-tile">
    <div class="kpi-value"><?= formatQty($totals['output'], 'count') ?></div>
    <div class="kpi-label">Sản lượng trong ngày</div>
  </div>
  <div class="kpi-tile">
    <div class="kpi-value"><?= formatQty($totals['target'], 'count') ?></div>
    <div class="kpi-label">Chỉ tiêu trong ngày</div>
  </div>
  <div class="kpi-tile <?= ($dayPct !== null && $dayPct < 1) ? 'kpi-risk' : '' ?>">
    <div class="kpi-value"><?= $dayPct !== null ? round($dayPct * 100) . '%' : '—' ?></div>
    <div class="kpi-label">Hiệu suất ngày</div>
  </div>
  <div class="kpi-tile <?= ($defectRate !== null && $defectRate > 0.02) ? 'kpi-risk' : '' ?>">
    <div class="kpi-value"><?= $defectRate !== null ? round($defectRate * 100, 1) . '%' : '—' ?></div>
    <div class="kpi-label">Tỷ lệ lỗi QC</div>
  </div>
</div>

<div class="card">
<h3>Lệnh chạy trong ngày</h3>
<?php if (!$orders): ?>
  <p class="hint">Không có lệnh sản xuất nào theo lịch trong ngày này.</p>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr><th></th><th>Mã lệnh</th><th>Khách hàng</th><th>Chuyền</th><th>Tiến độ chung</th><th>Hạn giao</th><th>Cảnh báo</th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o):
    $meta = $orderMeta[$o['id']];
    $color = $customerColors[(int) $o['customer_id']] ?? '#6B6459';
  ?>
    <tr>
      <td><span class="legend-swatch" style="background: <?= e($color) ?>"></span></td>
      <td><a href="<?= e(url('/lenh-san-xuat/' . $o['id'])) ?>"><?= e($o['order_code']) ?></a></td>
      <td><?= e($o['customer_name']) ?></td>
      <td><?= $o['chuyen'] ? e($o['chuyen']) : '<span class="not-confirmed">chưa phân chuyền</span>' ?></td>
      <td><?= $meta['pct_done'] !== null ? round($meta['pct_done'] * 100) . '% (' . formatQty($meta['output'], 'count') . '/' . formatQty($o['planned_quantity'], 'count') . ')' : displayValue(null) ?></td>
      <td><?= displayValue($o['current_due_date']) ?></td>
      <td><span class="badge <?= e($meta['badge']['css']) ?>"><?= e($meta['badge']['label']) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
</div>

<div class="card">
<h3>Báo cáo ca trong ngày</h3>
<?php if (!$reports): ?>
  <p class="hint">Chưa có báo cáo ca nào cho ngày này.</p>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr>
    <th>Chuyền</th><th>Lệnh</th><th>Sản lượng</th><th>Chỉ tiêu</th><th>Đạt</th>
    <th>Nhân sự</th><th>SL/người</th><th>QC kiểm</th><th>QC lỗi</th><th>Trạng thái</th>
  </tr></thead>
  <tbody>
  <?php foreach ($reports as $r):
    $pct = ($r['output_qty'] !== null && $r['target_qty']) ? (int) $r['output_qty'] / (int) $r['target_qty'] : null;
    $perWorker = ($r['output_qty'] !== null && $r['worker_count']) ? (int) $r['output_qty'] / (int) $r['worker_count'] : null;
  ?>
    <tr>
      <td><?= e($r['line']) ?></td>
      <td><a href="<?= e(url('/bao-cao-ca/' . $r['id'])) ?>"><?= e($r['order_code']) ?></a></td>
      <td><?= displayValue($r['output_qty']) ?></td>
      <td><?= displayValue($r['target_qty']) ?></td>
      <td class="<?= ($pct !== null && $pct < 1) ? 'cell-risk' : '' ?>"><?= $pct !== null ? round($pct * 100) . '%' : displayValue(null) ?></td>
      <td><?= displayValue($r['worker_count']) ?></td>
      <td><?= $perWorker !== null ? formatQty($perWorker) : displayValue(null) ?></td>
      <td><?= displayValue($r['qc_checked_qty']) ?></td>
      <td><?= displayValue($r['qc_defect_qty']) ?></td>
      <td><?= $r['is_locked'] ? '<span class="badge badge-good">Đã khóa</span>' : '<span class="badge badge-warn">Đang mở</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
</div>
