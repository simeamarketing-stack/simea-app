<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<h1>Tạo lệnh sản xuất</h1>
<p class="hint">Chỉ cần mã lệnh + khách hàng — các thông tin khác bổ sung sau tại trang chi tiết lệnh.</p>
<form method="post" action="<?= e(url('/lenh-san-xuat/tao')) ?>" class="stacked-form card">
  <?= Csrf::field() ?>
  <label>Mã lệnh
    <input type="text" name="order_code" value="<?= e(old($old, 'order_code')) ?>" required>
    <?= fieldError($errors, 'code') ?>
  </label>
  <label>Khách hàng
    <select name="customer_id" required>
      <option value="">— Chọn khách hàng —</option>
      <?php foreach ($customers as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) old($old, 'customer_id') === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['code']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <?= fieldError($errors, 'customer_id') ?>
  </label>
  <div>
    <button type="submit" class="btn-primary">Tạo lệnh</button>
    <a class="btn-secondary btn" href="<?= e(url('/lenh-san-xuat')) ?>">Hủy</a>
  </div>
</form>
