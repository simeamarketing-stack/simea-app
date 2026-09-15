<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$monthNames = ['', 'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$attentionLabels = [
    'missing_qty' => 'Thiếu số lượng kế hoạch',
    'missing_material' => 'Thiếu vật tư',
    'schedule_unconfirmed' => 'Chưa xác nhận lịch',
    'due_risk' => 'Nguy cơ trễ hạn',
    'stale_report' => 'Thiếu cập nhật báo cáo ca',
];
$today = date('Y-m-d');
?>
<div class="page-head">
  <h1>Dashboard</h1>
</div>

<div class="kpi-row">
  <a class="kpi-tile" href="<?= e(url('/dashboard/chi-tiet/lenh-dang-chay')) ?>">
    <div class="kpi-value"><?= (int) $activeCount ?></div>
    <div class="kpi-label">Lệnh đang chạy</div>
  </a>
  <a class="kpi-tile <?= $attentionCount ? 'kpi-risk' : '' ?>" href="<?= e(url('/dashboard/chi-tiet/lenh-can-chu-y')) ?>">
    <div class="kpi-value"><?= (int) $attentionCount ?></div>
    <div class="kpi-label">Lệnh cần chú ý</div>
  </a>
  <a class="kpi-tile <?= $lowStockCount ? 'kpi-risk' : '' ?>" href="<?= e(url('/dashboard/chi-tiet/vat-tu-sap-het')) ?>">
    <div class="kpi-value"><?= (int) $lowStockCount ?></div>
    <div class="kpi-label">Vật tư sắp hết</div>
  </a>
  <a class="kpi-tile" href="<?= e(url('/dashboard/chi-tiet/hieu-suat')) ?>">
    <div class="kpi-value"><?= $productivity['pct'] !== null ? round($productivity['pct'] * 100) . '%' : '—' ?></div>
    <div class="kpi-label">Hiệu suất 30 ngày</div>
  </a>
  <a class="kpi-tile <?= ($qcDefect['rate'] !== null && $qcDefect['rate'] > 0.02) ? 'kpi-risk' : '' ?>" href="<?= e(url('/dashboard/chi-tiet/loi-qc')) ?>">
    <div class="kpi-value"><?= $qcDefect['rate'] !== null ? round($qcDefect['rate'] * 100, 1) . '%' : '—' ?></div>
    <div class="kpi-label">Tỷ lệ lỗi QC 30 ngày</div>
  </a>
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
        <td class="<?= $day['date'] === $today ? 'calendar-today' : '' ?>">
          <?php if ($day['date']):
            $showLineHeaders = count($day['lines']) > 1;
            $showProgress = $day['orderCount'] < 4;
            $shown = 0;
          ?>
            <a class="calendar-daynum" href="<?= e(url('/dashboard/ngay/' . $day['date'])) ?>"><?= (int) substr($day['date'], 8, 2) ?></a>

            <?php foreach ($day['lines'] as $lineName => $lineOrders): ?>
              <?php if ($showLineHeaders && $shown < 3): ?>
                <div class="calendar-line-head"><?= $lineName === '' ? 'Chưa phân chuyền' : e($lineName) ?></div>
              <?php endif; ?>
              <?php foreach ($lineOrders as $o):
                if ($shown >= 3) { break 2; }
                $shown++;
                $meta = $orderMeta[$o['id']];
                $color = $customerColors[(int) $o['customer_id']] ?? '#6B6459';
                $tip = $o['order_code'] . ' — ' . $o['customer_name']
                    . ($o['chuyen'] ? ' — Chuyền ' . $o['chuyen'] : ' — chưa phân chuyền')
                    . ' — ' . $meta['badge']['label']
                    . ($meta['pct_done'] !== null ? ' — đã làm ' . round($meta['pct_done'] * 100) . '%' : '');
              ?>
                <a class="cal-pill <?= $meta['urgency'] ? 'cal-pill-' . e($meta['urgency']) : '' ?> <?= $lineName === '' ? 'cal-pill-unassigned' : '' ?>"
                   style="--pill-color: <?= e($color) ?>"
                   href="<?= e(url('/lenh-san-xuat/' . $o['id'])) ?>"
                   title="<?= e($tip) ?>">
                  <span class="cal-pill-text"><?= e($o['customer_name']) ?></span>
                  <?php if ($meta['urgency']): ?><span class="cal-pill-dot"></span><?php endif; ?>
                  <?php if ($showProgress && $meta['pct_done'] !== null): ?>
                    <span class="cal-pill-progress" style="width: <?= round($meta['pct_done'] * 100) ?>%"></span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            <?php endforeach; ?>

            <?php if ($day['orderCount'] > $shown): ?>
              <a class="cal-more" href="<?= e(url('/dashboard/ngay/' . $day['date'])) ?>">+<?= $day['orderCount'] - $shown ?> nữa</a>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <div class="cal-legend">
    <?php foreach ($customerLegend as $item): ?>
      <span class="legend-item"><span class="legend-swatch" style="background: <?= e($item['color']) ?>"></span><?= e($item['name']) ?></span>
    <?php endforeach; ?>
    <?php if (!$customerLegend): ?><span class="hint">Tháng này chưa có lệnh sản xuất nào.</span><?php endif; ?>
    <span class="legend-item"><span class="legend-dot legend-dot-urgent"></span>Gấp: thiếu vật tư / nguy cơ trễ hạn</span>
    <span class="legend-item"><span class="legend-dot legend-dot-warn"></span>Cần để ý: chưa xác nhận lịch / thiếu báo cáo</span>
    <span class="legend-item"><span class="legend-progress"></span>Vạch dưới pill = tiến độ đã làm</span>
  </div>
  <p class="hint">Bấm vào số ngày để xem sản lượng và hiệu quả của ngày đó.</p>
</div>

<div class="card">
<h3>Cần xử lý</h3>
<?php if (!$attentionCount): ?>
  <p class="hint">Không có lệnh nào cần chú ý — mọi thứ đang bình thường.</p>
<?php else: ?>
  <ul class="attention-lines">
  <?php foreach ($attentionLabels as $key => $label): ?>
    <?php if (!empty($attentionByKey[$key])): ?>
      <li>
        <a href="<?= e(url('/dashboard/chi-tiet/lenh-can-chu-y')) ?>">
          <?= e($label) ?> — <strong><?= count($attentionByKey[$key]) ?> lệnh</strong> &rarr;
        </a>
      </li>
    <?php endif; ?>
  <?php endforeach; ?>
  </ul>
<?php endif; ?>
</div>
