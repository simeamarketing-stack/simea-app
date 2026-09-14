<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$monthNames = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$attentionLabels = [
    'missing_qty' => 'Thiếu số lượng kế hoạch',
    'missing_material' => 'Thiếu vật tư (sắp đến hạn cần chú ý)',
    'schedule_unconfirmed' => 'Chưa xác nhận lịch',
    'due_risk' => 'Nguy cơ trễ hạn',
    'stale_report' => 'Thiếu cập nhật báo cáo ca',
];
?>
<div class="page-head">
  <h1>Dashboard</h1>
</div>

<div class="kpi-row">
  <div class="kpi-tile">
    <div class="kpi-value"><?= (int) $activeCount ?></div>
    <div class="kpi-label">Lệnh đang chạy</div>
  </div>
  <div class="kpi-tile <?= $attention ? 'kpi-risk' : '' ?>">
    <div class="kpi-value"><?= count($attention) ?></div>
    <div class="kpi-label">Lệnh cần chú ý</div>
  </div>
  <div class="kpi-tile <?= $lowStock ? 'kpi-risk' : '' ?>">
    <div class="kpi-value"><?= count($lowStock) ?></div>
    <div class="kpi-label">Vật tư sắp hết</div>
  </div>
  <div class="kpi-tile">
    <div class="kpi-value"><?= $productivity['pct'] !== null ? round($productivity['pct'] * 100) . '%' : '—' ?></div>
    <div class="kpi-label">Hiệu suất 30 ngày</div>
  </div>
  <div class="kpi-tile <?= ($qcDefect['rate'] !== null && $qcDefect['rate'] > 0.02) ? 'kpi-risk' : '' ?>">
    <div class="kpi-value"><?= $qcDefect['rate'] !== null ? round($qcDefect['rate'] * 100, 1) . '%' : '—' ?></div>
    <div class="kpi-label">Tỷ lệ lỗi QC 30 ngày</div>
  </div>
</div>

<div class="card">
  <div class="page-head">
    <h3><?= e($monthNames[$month]) ?> <?= (int) $year ?> — Lịch sản xuất</h3>
    <div>
      <a class="btn-secondary btn" href="<?= e(url('/dashboard?year=' . $prevYear . '&month=' . $prevMonth)) ?>">&larr; Tháng trước</a>
      <a class="btn-secondary btn" href="<?= e(url('/dashboard?year=' . $nextYear . '&month=' . $nextMonth)) ?>">Tháng sau &rarr;</a>
    </div>
  </div>
  <div class="calendar-wrap">
  <table class="calendar">
    <thead><tr><th>T2</th><th>T3</th><th>T4</th><th>T5</th><th>T6</th><th>T7</th><th>CN</th></tr></thead>
    <tbody>
    <?php foreach ($calendar as $week): ?>
      <tr>
      <?php foreach ($week as $day): ?>
        <td class="<?= $day['date'] === date('Y-m-d') ? 'calendar-today' : '' ?>">
          <?php if ($day['date']): ?>
            <div class="calendar-daynum"><?= (int) substr($day['date'], 8, 2) ?></div>
            <?php foreach ($day['orders'] as $o): ?>
              <a class="calendar-pill" style="background:<?= e($orderColors[$o['id']] ?? '#888') ?>"
                 href="<?= e(url('/lenh-san-xuat/' . $o['id'])) ?>"
                 title="<?= e($o['order_code'] . ' — ' . $o['customer_name'] . ($o['chuyen'] ? ' — Chuyền ' . $o['chuyen'] : '')) ?>">
                <?= e($o['customer_name']) ?><?= $o['chuyen'] ? ' · ' . e($o['chuyen']) : '' ?>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </td>
      <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card">
<h3>Cảnh báo &amp; việc cần làm gấp</h3>
<?php if (!$attention): ?>
  <p class="hint">Không có lệnh nào cần chú ý — mọi thứ đang bình thường.</p>
<?php else: ?>
  <?php foreach ($attentionLabels as $key => $label): ?>
    <?php if (!empty($attentionByKey[$key])): ?>
      <div class="alert-group">
        <div class="alert-group-label"><?= e($label) ?> (<?= count($attentionByKey[$key]) ?>)</div>
        <ul>
        <?php foreach ($attentionByKey[$key] as $o): ?>
          <li><a href="<?= e(url('/lenh-san-xuat/' . $o['id'])) ?>"><?= e($o['order_code']) ?></a> — <?= e($o['customer_name']) ?>
            <?= $o['current_due_date'] ? ' · hạn ' . e($o['current_due_date']) : '' ?></li>
        <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="card">
<h3>Vật tư sắp hết</h3>
<?php if (!$lowStock): ?>
  <p class="hint">Chưa có vật tư nào dưới ngưỡng cảnh báo.</p>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr><th>Vật tư</th><th>Khả dụng</th><th>Ngưỡng cảnh báo</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($lowStock as $m): ?>
    <tr class="row-risk">
      <td><?= e($m['name']) ?> (<?= e($m['code']) ?>)</td>
      <td><?= e((string) $m['available']) ?> <?= e($m['unit_of_measure']) ?></td>
      <td><?= displayValue($m['min_stock_alert']) ?></td>
      <td><a href="<?= e(url('/kho/vat-tu/' . $m['id'])) ?>">Chi tiết</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
</div>

<div class="card">
<h3>Tiến độ lệnh đang chạy</h3>
<?php if (!$progress): ?>
  <p class="hint">Chưa có lệnh nào đang chạy.</p>
<?php else: ?>
  <?php foreach ($progress as $p): ?>
    <div class="progress-row">
      <div class="progress-head">
        <a href="<?= e(url('/lenh-san-xuat/' . $p['order']['id'])) ?>"><?= e($p['order']['order_code']) ?></a> — <?= e($p['order']['customer_name']) ?>
        <?php if ($p['behind']): ?><span class="badge badge-risk">Chậm tiến độ</span><?php endif; ?>
      </div>
      <div class="progress-bar-track">
        <div class="progress-bar-fill" style="width:<?= min(100, round($p['pct_done'] * 100)) ?>%"></div>
        <?php if ($p['pct_time'] !== null): ?>
          <div class="progress-bar-marker" style="left:<?= min(100, round($p['pct_time'] * 100)) ?>%" title="Thời gian đã trôi qua"></div>
        <?php endif; ?>
      </div>
      <div class="progress-meta"><?= (int) $p['output'] ?> / <?= (int) $p['order']['planned_quantity'] ?> hộp (<?= round($p['pct_done'] * 100) ?>%)<?= $p['pct_time'] !== null ? ' · đã qua ' . round($p['pct_time'] * 100) . '% thời gian' : '' ?></div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<div class="card">
<h3>Chất lượng &amp; hao hụt</h3>
<p>Tỷ lệ lỗi QC (30 ngày gần nhất, chỉ tính ca đã nhập số liệu kiểm):
  <strong><?= $qcDefect['checked'] > 0 ? round($qcDefect['rate'] * 100, 2) . '% (' . $qcDefect['defect'] . '/' . $qcDefect['checked'] . ')' : 'Chưa có dữ liệu' ?></strong>
</p>
<?php if ($damaged): ?>
<div class="table-wrap" style="margin-top:10px">
<table>
  <thead><tr><th>Vật tư hỏng/hao hụt tháng này</th><th>Số lượng</th></tr></thead>
  <tbody>
  <?php foreach ($damaged as $d): ?>
    <tr><td><?= e($d['name']) ?></td><td><?= e((string) $d['damaged_qty']) ?> <?= e($d['unit_of_measure']) ?></td></tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php else: ?>
  <p class="hint">Chưa ghi nhận vật tư hỏng/hao hụt trong tháng này.</p>
<?php endif; ?>
</div>
