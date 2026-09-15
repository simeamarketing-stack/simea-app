<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>Báo cáo ca theo ngày</h1>
  <?php if (Auth::is(ROLE_VAN_HANH)): ?>
    <a class="btn" href="<?= e(url('/bao-cao-ca/ngay/' . $today)) ?>">Ghi báo cáo hôm nay</a>
  <?php endif; ?>
</div>

<div class="card">
  <form method="get" action="<?= e(url('/bao-cao-ca')) ?>" class="inline-line-form">
    <label style="font-weight:600; font-size:.87rem; color:var(--ink-muted)">Mở một ngày khác:</label>
    <input type="date" name="date" value="<?= e($today) ?>" required>
    <button type="submit" class="btn-secondary btn">Mở</button>
  </form>
</div>

<div class="table-wrap">
<table>
  <thead><tr>
    <th>Ngày</th><th>Số lệnh</th><th>Số dòng</th><th>Chỉ tiêu</th><th>Thực tế</th><th>Thành phẩm</th>
    <th>Đạt</th><th>Lỗi</th><th>QC xác nhận</th><th>Đã khóa</th>
  </tr></thead>
  <tbody>
  <?php foreach ($days as $d):
    $pct = $d['target_total'] > 0 ? $d['output_total'] / $d['target_total'] : null;
    // Chỉ tính lỗi trên các dòng đã khai thành phẩm — dòng chưa khai không
    // được coi là "hỏng hết".
    $hasFinished = (int) $d['finished_rows'] > 0;
    $defect = (int) $d['defect_total'];
    $defectRate = $d['defect_base'] > 0 ? $defect / (int) $d['defect_base'] : null;
  ?>
    <tr>
      <td><a href="<?= e(url('/bao-cao-ca/ngay/' . $d['report_date'])) ?>"><strong><?= e($d['report_date']) ?></strong></a></td>
      <td><?= (int) $d['order_count'] ?></td>
      <td><?= (int) $d['row_count'] ?></td>
      <td><?= formatQty($d['target_total'], 'count') ?></td>
      <td><?= formatQty($d['output_total'], 'count') ?></td>
      <td><?= $hasFinished ? formatQty($d['finished_total'], 'count') : displayValue(null) ?>
        <?php if ($hasFinished && (int) $d['finished_rows'] < (int) $d['row_count']): ?>
          <span class="hint">(<?= (int) $d['finished_rows'] ?>/<?= (int) $d['row_count'] ?> dòng)</span>
        <?php endif; ?>
      </td>
      <td class="<?= ($pct !== null && $pct < 1) ? 'cell-risk' : '' ?>"><?= $pct !== null ? round($pct * 100) . '%' : '—' ?></td>
      <td class="<?= ($defectRate !== null && $defectRate > 0.02) ? 'cell-risk' : '' ?>">
        <?= $defectRate !== null ? formatQty($defect, 'count') . ' (' . round($defectRate * 100, 1) . '%)' : displayValue(null) ?>
      </td>
      <td><?= (int) $d['qc_count'] ?>/<?= (int) $d['row_count'] ?></td>
      <td><?= (int) $d['locked_count'] ?>/<?= (int) $d['row_count'] ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$days): ?><tr><td colspan="10">Chưa có báo cáo ca nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
