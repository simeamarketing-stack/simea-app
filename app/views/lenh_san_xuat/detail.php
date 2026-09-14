<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$statusLabels = ['draft' => 'Nháp', 'released' => 'Đã phát hành', 'in_progress' => 'Đang chạy', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'];
$isDraft = $order['status'] === 'draft';
$canEditInfo = $isDraft && Auth::is(ROLE_DIEU_PHOI);
$canRelease = $isDraft && Auth::is(ROLE_DIEU_PHOI);
$canSchedule = !$isDraft && Auth::is(ROLE_XUONG);
$canReserve = !$isDraft && Auth::is(ROLE_DIEU_PHOI, ROLE_KHO);
$canIssue = Auth::is(ROLE_KHO);
?>
<div class="page-head">
  <h1><?= e($order['order_code']) ?> — <?= e($order['customer_name']) ?></h1>
  <div>
    <span class="badge <?= e($order['badge']['css']) ?>"><?= e($order['badge']['label']) ?></span>
    <span class="badge badge-info"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span>
  </div>
</div>

<?php if ($isDraft && $missing): ?>
  <div class="alert alert-error"><strong>Chưa thể phát hành</strong> — còn thiếu: <?= e(implode(', ', $missing)) ?>.</div>
<?php endif; ?>

<div class="card">
<h3>Thông tin lệnh</h3>
<?php if ($canEditInfo): ?>
<form method="post" action="<?= e(url('/lenh-san-xuat/' . $order['id'] . '/cap-nhat-thong-tin')) ?>" class="stacked-form">
  <?= Csrf::field() ?>
  <label>SKU
    <select name="sku_id" onchange="this.form.submit()">
      <option value="">— Chọn SKU —</option>
      <?php foreach ($skus as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (int) $order['sku_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['code']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <?php if ($order['sku_id']): ?>
  <label>Phiên bản BOM
    <select name="bom_version_id">
      <option value="">— Chọn BOM —</option>
      <?php foreach ($bomVersions as $v): ?>
        <option value="<?= (int) $v['id'] ?>" <?= (int) $order['bom_version_id'] === (int) $v['id'] ? 'selected' : '' ?>>
          v<?= (int) $v['version_number'] ?> — <?= $v['status'] === 'approved' ? 'Đã duyệt' : 'Nháp' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Định mức năng suất
    <select name="yield_norm_id">
      <option value="">— Chọn định mức —</option>
      <?php foreach ($yieldVersions as $v): ?>
        <option value="<?= (int) $v['id'] ?>" <?= (int) $order['yield_norm_id'] === (int) $v['id'] ? 'selected' : '' ?>>
          v<?= (int) $v['version_number'] ?> — <?= $v['status'] === 'approved' ? 'Đã duyệt' : 'Nháp' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Số lượng kế hoạch
    <input type="number" name="planned_quantity" value="<?= e($order['planned_quantity'] ?? '') ?>">
  </label>
  <label>Ngày bắt đầu dự kiến
    <input type="date" name="planned_start_date" value="<?= e($order['planned_start_date'] ?? '') ?>">
  </label>
  <?php endif; ?>
  <div><button type="submit" class="btn-primary">Lưu thông tin</button></div>
</form>
<?php else: ?>
  <p>SKU: <?= displayValue($order['sku_code'] ?? null) ?></p>
  <p>BOM (đã đóng băng): <?= $order['released_bom_version_id'] ? 'v' . (int) $order['bom_version_number'] : displayValue(null) ?></p>
  <p>Định mức (đã đóng băng): <?= $order['released_yield_norm_id'] ? 'v' . (int) $order['yield_version_number'] : displayValue(null) ?></p>
  <p>Số lượng kế hoạch: <?= displayValue($order['planned_quantity']) ?></p>
  <p>Ngày bắt đầu dự kiến: <?= displayValue($order['planned_start_date']) ?></p>
<?php endif; ?>
</div>

<?php if ($canRelease): ?>
<form method="post" action="<?= e(url('/lenh-san-xuat/' . $order['id'] . '/phat-hanh')) ?>" onsubmit="return confirm('Phát hành lệnh sản xuất này?');">
  <?= Csrf::field() ?>
  <button type="submit" class="btn-primary">Phát hành lệnh</button>
</form>
<?php endif; ?>

<?php if (!$isDraft): ?>
<div class="card">
<h3>Lịch sản xuất</h3>
<p>Hạn giao gốc: <strong><?= displayValue($order['original_due_date']) ?></strong>
&nbsp;·&nbsp; Hạn giao hiện hành: <strong><?= displayValue($order['current_due_date']) ?></strong></p>

<?php if ($order['schedule_confirmed_at'] === null): ?>
  <?php if ($canSchedule): ?>
  <form method="post" action="<?= e(url('/lenh-san-xuat/' . $order['id'] . '/xac-nhan-lich')) ?>" class="stacked-form">
    <?= Csrf::field() ?>
    <?php if ($suggestedDueDate): ?><p class="hint">Hệ thống gợi ý hạn giao: <?= e($suggestedDueDate) ?> (theo định mức năng suất, có thể sửa).</p><?php endif; ?>
    <label>Hạn giao <input type="date" name="due_date" value="<?= e($suggestedDueDate ?? '') ?>" required></label>
    <div><button type="submit" class="btn-primary">Xác nhận lịch lần đầu</button></div>
  </form>
  <?php else: ?>
    <p class="alert alert-warn">Chưa xác nhận lịch — chờ Xưởng xác nhận lần đầu.</p>
  <?php endif; ?>
<?php elseif ($canSchedule): ?>
  <form method="post" action="<?= e(url('/lenh-san-xuat/' . $order['id'] . '/doi-lich')) ?>" class="stacked-form">
    <?= Csrf::field() ?>
    <label>Ngày mới <input type="date" name="new_date" required></label>
    <label>Lý do đổi lịch <input type="text" name="reason" required></label>
    <div><button type="submit" class="btn-secondary btn">Đổi lịch</button></div>
  </form>
<?php endif; ?>

<?php if ($scheduleLog): ?>
<div class="table-wrap" style="margin-top:12px">
<table>
  <thead><tr><th>Từ ngày</th><th>Sang ngày</th><th>Lý do</th><th>Người đổi</th><th>Thời gian</th></tr></thead>
  <tbody>
  <?php foreach ($scheduleLog as $log): ?>
    <tr>
      <td><?= displayValue($log['old_date']) ?></td>
      <td><?= e($log['new_date']) ?></td>
      <td><?= e($log['reason']) ?></td>
      <td><?= e($log['changed_by_name']) ?></td>
      <td><?= e($log['changed_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
</div>

<div class="card">
<h3>Chuyền &amp; người phụ trách</h3>
<?php if (Auth::is(ROLE_XUONG)): ?>
<form method="post" action="<?= e(url('/lenh-san-xuat/' . $order['id'] . '/gan-nhan-su')) ?>" class="stacked-form">
  <?= Csrf::field() ?>
  <label>Chuyền <input type="text" name="chuyen" value="<?= e($order['chuyen'] ?? '') ?>"></label>
  <label>Người phụ trách
    <select name="phu_trach_user_id">
      <option value="">— Chọn người phụ trách —</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int) $u['id'] ?>" <?= (int) $order['phu_trach_user_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div><button type="submit" class="btn-secondary btn">Lưu</button></div>
</form>
<form method="post" action="<?= e(url('/lenh-san-xuat/' . $order['id'] . '/cap-nhat-trang-thai')) ?>" class="stacked-form" style="margin-top:10px">
  <?= Csrf::field() ?>
  <label>Trạng thái
    <select name="status">
      <?php foreach ($statusLabels as $key => $label): ?>
        <option value="<?= e($key) ?>" <?= $order['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <div><button type="submit" class="btn-secondary btn">Cập nhật trạng thái</button></div>
</form>
<?php else: ?>
  <p>Chuyền: <?= displayValue($order['chuyen']) ?> · Người phụ trách: <?= displayValue($order['phu_trach_name'] ?? null) ?></p>
<?php endif; ?>
</div>

<div class="card">
<h3>Vật tư (theo BOM đã đóng băng)</h3>
<div class="table-wrap">
<table>
  <thead><tr><th>Vật tư</th><th>Định lượng/đơn vị</th><th>% dự phòng</th><th>% hao hụt</th></tr></thead>
  <tbody>
  <?php foreach ($bomLines as $line): ?>
    <tr>
      <td><?= e($line['material_name']) ?></td>
      <td><?= displayValue($line['quantity']) ?> (<?= $line['basis_unit'] === 'per_box' ? 'theo hộp' : 'theo cup' ?>)</td>
      <td><?= displayValue($line['buffer_pct'], '%') ?></td>
      <td><?= displayValue($line['waste_pct'], '%') ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$bomLines): ?><tr><td colspan="4">Chưa có BOM.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<h3>Giữ chỗ &amp; xuất kho</h3>
<div class="table-wrap">
<table>
  <thead><tr><th>Vật tư</th><th>Đã giữ</th><th>Đã xuất</th><th>Trạng thái</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($reservations as $r): ?>
    <tr>
      <td><?= e($r['material_name']) ?></td>
      <td><?= e((string) $r['quantity_reserved']) ?> <?= e($r['unit_of_measure']) ?></td>
      <td><?= e((string) $r['issued_qty']) ?></td>
      <td><?= e($r['status']) ?></td>
      <td>
        <?php if ($r['status'] === 'active'): ?>
          <?php if ($canIssue): ?>
          <form method="post" action="<?= e(url('/lenh-san-xuat/reservation/' . $r['id'] . '/xuat')) ?>" class="inline-line-form">
            <?= Csrf::field() ?>
            <input type="number" step="0.0001" name="quantity" placeholder="SL xuất" required>
            <button type="submit" class="btn-secondary btn">Xuất</button>
          </form>
          <?php endif; ?>
          <?php if ($canReserve): ?>
          <form method="post" action="<?= e(url('/lenh-san-xuat/reservation/' . $r['id'] . '/huy')) ?>" onsubmit="return confirm('Hủy giữ chỗ này?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-secondary btn">Hủy giữ chỗ</button>
          </form>
          <?php endif; ?>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$reservations): ?><tr><td colspan="5">Chưa giữ chỗ vật tư nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php if ($canReserve): ?>
<form method="post" action="<?= e(url('/lenh-san-xuat/' . $order['id'] . '/giu-cho')) ?>" class="inline-line-form" style="margin-top:12px">
  <?= Csrf::field() ?>
  <select name="material_id" required>
    <option value="">— Chọn vật tư để giữ chỗ —</option>
    <?php foreach ($materials as $m): ?>
      <option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="number" step="0.0001" name="quantity" placeholder="Số lượng" required>
  <button type="submit" class="btn">Giữ chỗ</button>
</form>
<?php endif; ?>
</div>
<?php endif; ?>
