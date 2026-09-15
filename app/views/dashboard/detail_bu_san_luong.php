<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$showCost = Auth::is(ROLE_QUAN_LY);
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn-secondary btn" href="<?= e(url($backUrl)) ?>">&larr; Về dashboard</a>
</div>

<?php if (!$rows): ?>
  <div class="card"><p class="hint">Không có ca nào hụt chỉ tiêu trong <?= (int) $windowDays ?> ngày gần nhất.</p></div>
<?php else: ?>

  <?php if ($showCost):
    $totalPicked = 0;
    foreach ($rows as $r) {
      if ($r['advice']) {
        $key = $r['advice']['recommended'];
        $totalPicked += $key === 'combo' ? $r['advice']['combo']['cost'] : $r['advice'][$key]['cost'];
      }
    }
  ?>
  <div class="card">
    <p>Nếu làm theo toàn bộ đề xuất bên dưới, tổng chi phí nhân công phát sinh khoảng
      <strong><?= formatMoney($totalPicked) ?></strong>.</p>
    <p class="hint">Chỉ Quản lý thấy phần chi phí. Vận hành chỉ thấy số giờ tăng ca / số người cần bổ sung.</p>
  </div>
  <?php endif; ?>

  <?php foreach ($rows as $r): $rp = $r['report']; ?>
    <div class="card">
      <div class="page-head">
        <h3><?= e($rp['report_date']) ?> — <?= e($rp['order_code']) ?>
          <span class="hint"><?= e($rp['customer_name']) ?> · <?= e($rp['line']) ?></span>
        </h3>
        <a class="btn-secondary btn" href="<?= e(url('/bao-cao-ca/' . $rp['id'])) ?>">Mở báo cáo ca</a>
      </div>

      <div class="table-wrap">
      <table>
        <thead><tr><th>Sản lượng</th><th>Chỉ tiêu</th><th>Hụt</th><th>Đạt</th><th>Nhân sự ca đó</th><th>Chỉ tiêu bù đã ghi</th></tr></thead>
        <tbody>
          <tr>
            <td><?= formatQty($rp['output_qty'], 'count') ?></td>
            <td><?= formatQty($rp['target_qty'], 'count') ?></td>
            <td class="cell-risk"><?= formatQty($r['shortfall'], 'count') ?></td>
            <td><?= round((int) $rp['output_qty'] / (int) $rp['target_qty'] * 100) ?>%</td>
            <td><?= displayValue($rp['worker_count']) ?></td>
            <td><?= displayValue($rp['catch_up_target_tomorrow']) ?></td>
          </tr>
        </tbody>
      </table>
      </div>

      <?php if ($r['advice']): $advice = $r['advice']; ?>
        <?php require APP_PATH . '/views/partials/catchup_advice.php'; ?>
      <?php else: ?>
        <p class="hint" style="margin-top:12px">Lệnh này chưa có định mức năng suất đã duyệt (số người chuẩn, giờ/ngày, hộp/giờ)
          nên chưa tính được nên tăng ca hay thêm người. Bổ sung ở mục Định mức năng suất của SKU.</p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

<?php endif; ?>
