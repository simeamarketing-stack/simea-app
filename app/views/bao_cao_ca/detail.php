<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$isLocked = (int) $report['is_locked'] === 1;
$canEdit = !$isLocked && Auth::is(ROLE_VAN_HANH, ROLE_QUAN_LY);
$canConfirmQc = !$isLocked && !$report['qc_confirmed_by'] && Auth::is(ROLE_VAN_HANH);
$canLock = !$isLocked && Auth::is(ROLE_VAN_HANH);
?>
<div class="page-head">
  <h1>Báo cáo ca — <?= e($report['order_code']) ?> — <?= e($report['report_date']) ?> — <?= e($report['line']) ?></h1>
  <?= $isLocked ? '<span class="badge badge-good">Đã khóa</span>' : '<span class="badge badge-warn">Đang mở</span>' ?>
</div>

<div class="card">
<?php if ($canEdit): ?>
<form method="post" action="<?= e(url('/bao-cao-ca/' . $report['id'] . '/cap-nhat')) ?>" class="stacked-form">
  <?= Csrf::field() ?>
  <label>Sản lượng thực tế <input type="number" name="output_qty" value="<?= e($report['output_qty'] ?? '') ?>"></label>
  <label>Chỉ tiêu ca (hộp) <input type="number" name="target_qty" value="<?= e($report['target_qty'] ?? '') ?>"></label>
  <label>Số nhân sự <input type="number" name="worker_count" value="<?= e($report['worker_count'] ?? '') ?>"></label>
  <label>Sự cố <textarea name="incidents" rows="2"><?= e($report['incidents'] ?? '') ?></textarea></label>

  <div class="catchup-box" data-catchup-box>
    <div class="catchup-label">Nếu hụt chỉ tiêu — bắt buộc điền</div>
    <label>Chỉ tiêu bù ngày mai <input type="number" name="catch_up_target_tomorrow" value="<?= e($report['catch_up_target_tomorrow'] ?? '') ?>"></label>
    <label>Kế hoạch khắc phục <textarea name="remediation_plan" rows="2"><?= e($report['remediation_plan'] ?? '') ?></textarea></label>
  </div>

  <label>Lý do sửa (bắt buộc)
    <input type="text" name="reason" required>
  </label>
  <div><button type="submit" class="btn-primary">Lưu thay đổi</button></div>
</form>
<?php else: ?>
  <p>Sản lượng thực tế: <?= displayValue($report['output_qty']) ?> · Chỉ tiêu: <?= displayValue($report['target_qty']) ?></p>
  <p>Số nhân sự: <?= displayValue($report['worker_count']) ?></p>
  <p>Sự cố: <?= $report['incidents'] ? e($report['incidents']) : '(không có)' ?></p>
  <p>Chỉ tiêu bù ngày mai: <?= displayValue($report['catch_up_target_tomorrow']) ?></p>
  <p>Kế hoạch khắc phục: <?= displayValue($report['remediation_plan']) ?></p>
<?php endif; ?>
</div>

<div class="card">
  <p>Người ghi: <?= e($report['logged_by_name'] ?? '') ?> · <?= e($report['logged_at'] ?? '') ?></p>
  <p>QC xác nhận: <?= $report['qc_confirmed_by'] ? e($report['qc_confirmed_by_name']) . ' · ' . e($report['qc_confirmed_at']) : 'Chưa xác nhận' ?></p>
  <?php if ($report['qc_confirmed_by']): ?>
    <p>Số lượng đã kiểm: <?= displayValue($report['qc_checked_qty'] ?? null) ?> · Số lượng lỗi/hư hỏng: <?= displayValue($report['qc_defect_qty'] ?? null) ?></p>
  <?php endif; ?>
  <?php if ($canConfirmQc): ?>
    <form method="post" action="<?= e(url('/bao-cao-ca/' . $report['id'] . '/qc-xac-nhan')) ?>" class="stacked-form">
      <?= Csrf::field() ?>
      <label>Số lượng đã kiểm (không bắt buộc)
        <input type="number" name="qc_checked_qty">
      </label>
      <label>Số lượng lỗi/hư hỏng (không bắt buộc)
        <input type="number" name="qc_defect_qty">
      </label>
      <div><button type="submit" class="btn-secondary btn">QC xác nhận</button></div>
    </form>
  <?php endif; ?>
  <?php if ($isLocked): ?>
    <p>Đã khóa bởi: <?= e($report['locked_by_name'] ?? '') ?> · <?= e($report['locked_at'] ?? '') ?></p>
  <?php elseif ($canLock): ?>
    <form method="post" action="<?= e(url('/bao-cao-ca/' . $report['id'] . '/khoa')) ?>" onsubmit="return confirm('Khóa báo cáo này? Sau khi khóa sẽ không sửa được nữa.');">
      <?= Csrf::field() ?>
      <button type="submit" class="btn-primary">Khóa báo cáo</button>
    </form>
  <?php endif; ?>
</div>

<?php if ($editHistory): ?>
<div class="card">
<h3>Lịch sử chỉnh sửa</h3>
<div class="table-wrap">
<table>
  <thead><tr><th>Trường</th><th>Giá trị cũ</th><th>Giá trị mới</th><th>Lý do</th><th>Người sửa</th><th>Thời gian</th></tr></thead>
  <tbody>
  <?php foreach ($editHistory as $log): ?>
    <tr>
      <td><?= e($log['field_name']) ?></td>
      <td><?= displayValue($log['old_value']) ?></td>
      <td><?= displayValue($log['new_value']) ?></td>
      <td><?= e($log['reason']) ?></td>
      <td><?= e($log['edited_by_name']) ?></td>
      <td><?= e($log['edited_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<?php endif; ?>
