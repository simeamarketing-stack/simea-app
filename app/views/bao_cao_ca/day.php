<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$weekdays = [1 => 'Thứ 2', 2 => 'Thứ 3', 3 => 'Thứ 4', 4 => 'Thứ 5', 5 => 'Thứ 6', 6 => 'Thứ 7', 7 => 'Chủ nhật'];
$ts = strtotime($date);
$canEdit = Auth::is(ROLE_VAN_HANH, ROLE_QUAN_LY);
$hasExisting = false;
foreach ($rows as $r) { if ($r['report']) { $hasExisting = true; break; } }
?>
<div class="page-head">
  <h1><?= e($weekdays[(int) date('N', $ts)]) ?>, <?= e(date('d/m/Y', $ts)) ?></h1>
  <div>
    <a class="btn-secondary btn" href="<?= e(url('/bao-cao-ca/ngay/' . date('Y-m-d', strtotime('-1 day', $ts)))) ?>">&larr; Hôm trước</a>
    <a class="btn-secondary btn" href="<?= e(url('/bao-cao-ca/ngay/' . date('Y-m-d', strtotime('+1 day', $ts)))) ?>">Hôm sau &rarr;</a>
    <a class="btn-secondary btn" href="<?= e(url('/bao-cao-ca')) ?>">Danh sách ngày</a>
  </div>
</div>

<?php if (!$rows): ?>
  <div class="card">
    <p class="hint">Ngày này không có lệnh sản xuất nào theo lịch, và cũng chưa có báo cáo nào.
      Kiểm tra lại lịch của lệnh ở mục <a href="<?= e(url('/lenh-san-xuat')) ?>">Lệnh sản xuất</a>.</p>
  </div>
<?php else: ?>

<form method="post" action="<?= e(url('/bao-cao-ca/ngay/' . $date)) ?>">
  <?= Csrf::field() ?>
  <div class="table-wrap">
  <table class="day-table">
    <thead><tr>
      <th>Lệnh</th><th>Công ty</th><th>SKU</th><th>Chuyền</th>
      <th>Chỉ tiêu</th><th>Thực tế</th><th>Thành phẩm</th><th>Lỗi</th>
      <th>Nhân công</th><th>Sự cố</th><th>Trạng thái</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $i => $r):
      $o = $r['order'];
      $rp = $r['report'];
      $locked = $rp && (int) $rp['is_locked'] === 1;
      $defect = ($rp && $rp['output_qty'] !== null && $rp['finished_qty'] !== null)
        ? (int) $rp['output_qty'] - (int) $rp['finished_qty'] : null;
      $short = ($rp && $rp['output_qty'] !== null && $rp['target_qty'] !== null)
        ? (int) $rp['target_qty'] - (int) $rp['output_qty'] : 0;
      $needsCatchUp = $short > 0 && ($rp['catch_up_target_tomorrow'] === null || $rp['remediation_plan'] === null);
    ?>
      <tr class="<?= $r['is_new'] ? 'day-row-new' : '' ?> <?= $locked ? 'day-row-locked' : '' ?>">
        <td>
          <?php if ($r['is_new']): ?>
            <span class="hint">+ dòng mới</span>
          <?php else: ?>
            <a href="<?= e(url('/bao-cao-ca/' . $rp['id'])) ?>"><?= e($o['order_code']) ?></a>
          <?php endif; ?>
          <input type="hidden" name="rows[<?= $i ?>][order_id]" value="<?= (int) $o['id'] ?>">
          <input type="hidden" name="rows[<?= $i ?>][report_id]" value="<?= $rp ? (int) $rp['id'] : 0 ?>">
        </td>
        <td><?= e($o['customer_name']) ?></td>
        <td><?= e($o['sku_code'] ?? '') ?></td>
        <td>
          <?php if ($locked): ?>
            <?= e($rp['line']) ?>
          <?php elseif ($rp): ?>
            <?= e($rp['line']) ?>
            <input type="hidden" name="rows[<?= $i ?>][line]" value="<?= e($rp['line']) ?>">
          <?php else: ?>
            <input type="text" name="rows[<?= $i ?>][line]" value="<?= e($o['chuyen'] ?? '') ?>" placeholder="Chuyền">
          <?php endif; ?>
        </td>
        <?php if ($locked): ?>
          <td><?= displayValue($rp['target_qty']) ?></td>
          <td><?= displayValue($rp['output_qty']) ?></td>
          <td><?= displayValue($rp['finished_qty']) ?></td>
          <td><?= $defect !== null ? formatQty($defect, 'count') : displayValue(null) ?></td>
          <td><?= displayValue($rp['worker_count']) ?></td>
          <td><?= $rp['incidents'] ? e($rp['incidents']) : '' ?></td>
        <?php else: ?>
          <td><input type="number" class="cell-num" name="rows[<?= $i ?>][target_qty]"
                     value="<?= e($rp ? $rp['target_qty'] : ($r['suggested_target'] ?? '')) ?>"></td>
          <td><input type="number" class="cell-num js-output" name="rows[<?= $i ?>][output_qty]" value="<?= e($rp['output_qty'] ?? '') ?>"></td>
          <td><input type="number" class="cell-num js-finished" name="rows[<?= $i ?>][finished_qty]" value="<?= e($rp['finished_qty'] ?? '') ?>"></td>
          <td class="js-defect"><?= $defect !== null ? formatQty($defect, 'count') : '' ?></td>
          <td><input type="number" class="cell-num" name="rows[<?= $i ?>][worker_count]" value="<?= e($rp['worker_count'] ?? '') ?>"></td>
          <td><input type="text" class="cell-text" name="rows[<?= $i ?>][incidents]" value="<?= e($rp['incidents'] ?? '') ?>"></td>
        <?php endif; ?>
        <td>
          <?php if ($locked): ?>
            <span class="badge badge-good">Đã khóa</span>
          <?php elseif ($rp): ?>
            <?php if ($needsCatchUp): ?>
              <a class="badge badge-risk" href="<?= e(url('/bao-cao-ca/' . $rp['id'])) ?>">Cần điền chỉ tiêu bù</a>
            <?php elseif ($rp['qc_confirmed_by']): ?>
              <span class="badge badge-info">QC đã xác nhận</span>
            <?php else: ?>
              <span class="badge badge-warn">Chờ QC</span>
            <?php endif; ?>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <?php if ($canEdit): ?>
  <div class="card" style="margin-top:16px">
    <?php if ($hasExisting): ?>
    <label class="day-reason">Lý do sửa <span class="hint">(bắt buộc nếu bạn sửa số liệu của dòng đã ghi trước đó)</span>
      <input type="text" name="reason" placeholder="Ví dụ: đếm lại cuối ca, sai số khi nhập">
    </label>
    <?php endif; ?>
    <div style="margin-top:12px">
      <button type="submit" class="btn-primary">Lưu báo cáo ngày này</button>
    </div>
    <p class="hint" style="margin-top:10px">
      Cột <strong>Lỗi</strong> tự tính bằng Thực tế − Thành phẩm, không cần nhập.
      Dòng nào hụt chỉ tiêu sẽ hiện nhắc điền chỉ tiêu bù — mở dòng đó ra để điền và xem đề xuất tăng ca / thêm người.
      Dòng đã khóa thì không sửa được nữa.
    </p>
  </div>
  <?php endif; ?>
</form>

<?php endif; ?>
