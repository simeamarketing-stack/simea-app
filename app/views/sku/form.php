<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$isEdit = $sku !== null;
$action = $isEdit ? '/danh-muc/sku/' . $sku['id'] . '/sua' : '/danh-muc/sku/tao';
$currentCustomerId = $isEdit ? $sku['customer_id'] : old($old, 'customer_id');
$currentCoffeeTypeId = $isEdit ? $sku['coffee_type_id'] : old($old, 'coffee_type_id');
?>
<h1><?= $isEdit ? 'Sửa SKU' : 'Thêm SKU' ?></h1>
<form method="post" action="<?= e(url($action)) ?>" class="stacked-form card">
  <?= Csrf::field() ?>
  <label>Mã SKU
    <input type="text" name="code" value="<?= e($isEdit ? $sku['code'] : old($old, 'code')) ?>" required>
    <?= fieldError($errors, 'code') ?>
  </label>
  <label>Khách hàng
    <select name="customer_id" required>
      <option value="">— Chọn khách hàng —</option>
      <?php foreach ($customers as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= (int) $currentCustomerId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['code']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <?= fieldError($errors, 'customer_id') ?>
  </label>
  <label>Loại cà phê
    <select name="coffee_type_id" required>
      <option value="">— Chọn loại cà phê —</option>
      <?php foreach ($coffeeTypes as $t): ?>
        <option value="<?= (int) $t['id'] ?>" <?= (int) $currentCoffeeTypeId === (int) $t['id'] ? 'selected' : '' ?>><?= e($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?= fieldError($errors, 'coffee_type_id') ?>
  </label>
  <label>Số viên/hộp
    <input type="number" name="units_per_box" min="1" value="<?= e((string) ($isEdit ? $sku['units_per_box'] : old($old, 'units_per_box'))) ?>" required>
    <?= fieldError($errors, 'units_per_box') ?>
  </label>
  <label>Mô tả
    <textarea name="description" rows="2"><?= e($isEdit ? ($sku['description'] ?? '') : old($old, 'description')) ?></textarea>
  </label>
  <div>
    <button type="submit" class="btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo SKU' ?></button>
    <a class="btn-secondary btn" href="<?= e(url('/danh-muc/sku')) ?>">Hủy</a>
  </div>
</form>
