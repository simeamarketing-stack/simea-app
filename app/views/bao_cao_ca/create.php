<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<h1>Ghi báo cáo ca</h1>
<?php if (!empty($errors['shortfall'])): ?><div class="alert alert-error"><?= e($errors['shortfall']) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('/bao-cao-ca/tao')) ?>" class="stacked-form card">
  <?= Csrf::field() ?>
  <label>Lệnh sản xuất
    <select name="production_order_id" data-order-select required>
      <option value="">— Chọn lệnh —</option>
      <?php foreach ($orders as $o): ?>
        <option value="<?= (int) $o['id'] ?>" data-suggested-target="<?= e((string) ($o['suggested_target'] ?? '')) ?>"
          <?= (int) old($old, 'production_order_id') === (int) $o['id'] ? 'selected' : '' ?>>
          <?= e($o['order_code']) ?> — <?= e($o['customer_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?= fieldError($errors, 'production_order_id') ?>
  </label>
  <label>Ngày báo cáo
    <input type="date" name="report_date" value="<?= e(old($old, 'report_date')) ?>" required>
    <?= fieldError($errors, 'report_date') ?>
  </label>
  <label>Chuyền
    <input type="text" name="line" value="<?= e(old($old, 'line')) ?>" required>
    <?= fieldError($errors, 'line') ?>
  </label>
  <label>Sản lượng thực tế
    <input type="number" name="output_qty" value="<?= e(old($old, 'output_qty')) ?>">
  </label>
  <label>Chỉ tiêu ca (hộp)
    <input type="number" name="target_qty" value="<?= e(old($old, 'target_qty')) ?>">
  </label>
  <label>Số nhân sự
    <input type="number" name="worker_count" value="<?= e(old($old, 'worker_count')) ?>">
  </label>
  <label>Sự cố (nếu có)
    <textarea name="incidents" rows="2"><?= e(old($old, 'incidents')) ?></textarea>
  </label>

  <div class="catchup-box" data-catchup-box>
    <div class="catchup-label">Nếu hụt chỉ tiêu — bắt buộc điền</div>
    <label>Chỉ tiêu bù ngày mai
      <input type="number" name="catch_up_target_tomorrow" value="<?= e(old($old, 'catch_up_target_tomorrow')) ?>">
    </label>
    <label>Kế hoạch khắc phục
      <textarea name="remediation_plan" rows="2"><?= e(old($old, 'remediation_plan')) ?></textarea>
    </label>
  </div>

  <div>
    <button type="submit" class="btn-primary">Ghi báo cáo</button>
    <a class="btn-secondary btn" href="<?= e(url('/bao-cao-ca')) ?>">Hủy</a>
  </div>
</form>
