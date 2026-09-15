<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn-secondary btn" href="<?= e(url($backUrl)) ?>">&larr; Về dashboard</a>
</div>

<div class="card">
  <p>Tổng 30 ngày gần nhất:
    <strong>
      <?= $summary['checked'] > 0
        ? formatQty($summary['defect'], 'count') . ' lỗi / ' . formatQty($summary['checked'], 'count') . ' hộp làm ra — ' . round($summary['rate'] * 100, 2) . '%'
        : 'Chưa có dữ liệu' ?>
    </strong>
  </p>
  <p class="hint">Lỗi = số lượng thực tế − số lượng thành phẩm. Chỉ tính các ca đã khai cả hai số — ca chưa khai thành phẩm không bị coi là "không lỗi".</p>
</div>

<div class="card">
<h3>Từng ca có số liệu QC</h3>
<?php if (!$reports): ?>
  <p class="hint">Chưa ca nào khai đủ số lượng thực tế và thành phẩm trong 30 ngày gần nhất — khai ở bảng báo cáo ca theo ngày.</p>
<?php else: ?>
<div class="table-wrap">
<table>
  <thead><tr>
    <th>Ngày</th><th>Lệnh</th><th>Khách hàng</th><th>Chuyền</th>
    <th>Thực tế</th><th>Lỗi</th><th>Tỷ lệ lỗi</th><th>Sự cố ghi nhận</th>
  </tr></thead>
  <tbody>
  <?php foreach ($reports as $r):
    $checked = (int) $r['output_qty'];
    $defect = (int) $r['output_qty'] - (int) $r['finished_qty'];
    $rate = $checked > 0 ? $defect / $checked : null;
  ?>
    <tr>
      <td><?= e($r['report_date']) ?></td>
      <td><a href="<?= e(url('/bao-cao-ca/' . $r['id'])) ?>"><?= e($r['order_code']) ?></a></td>
      <td><?= e($r['customer_name']) ?></td>
      <td><?= e($r['line']) ?></td>
      <td><?= formatQty($checked, 'count') ?></td>
      <td><?= formatQty($defect, 'count') ?></td>
      <td class="<?= ($rate !== null && $rate > 0.02) ? 'cell-risk' : '' ?>"><?= $rate !== null ? round($rate * 100, 2) . '%' : displayValue(null) ?></td>
      <td><?= $r['incidents'] ? e($r['incidents']) : '(không có)' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
</div>
