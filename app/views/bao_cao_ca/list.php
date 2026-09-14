<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>Báo cáo ca</h1>
  <?php if (Auth::is(ROLE_VAN_HANH)): ?>
    <a class="btn" href="<?= e(url('/bao-cao-ca/tao')) ?>">+ Ghi báo cáo ca</a>
  <?php endif; ?>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Ngày</th><th>Lệnh SX</th><th>Chuyền</th><th>Sản lượng</th><th>Chỉ tiêu</th><th>QC xác nhận</th><th>Trạng thái</th></tr></thead>
  <tbody>
  <?php foreach ($reports as $r): ?>
    <tr>
      <td><?= e($r['report_date']) ?></td>
      <td><a href="<?= e(url('/bao-cao-ca/' . $r['id'])) ?>"><?= e($r['order_code']) ?></a></td>
      <td><?= e($r['line']) ?></td>
      <td><?= displayValue($r['output_qty']) ?></td>
      <td><?= displayValue($r['target_qty']) ?></td>
      <td><?= $r['qc_confirmed_at'] ? 'Đã xác nhận' : 'Chưa xác nhận' ?></td>
      <td><?= $r['is_locked'] ? '<span class="badge badge-good">Đã khóa</span>' : '<span class="badge badge-warn">Đang mở</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$reports): ?><tr><td colspan="7">Chưa có báo cáo ca nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
