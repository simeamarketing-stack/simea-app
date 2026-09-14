<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$isEdit = $material !== null;
$action = $isEdit ? '/danh-muc/vat-tu/' . $material['id'] . '/sua' : '/danh-muc/vat-tu/tao';
$currentGroupId = $isEdit ? $material['material_group_id'] : old($old, 'material_group_id');
$currentUnitType = $isEdit ? $material['unit_type'] : old($old, 'unit_type');
?>
<h1><?= $isEdit ? 'Sửa vật tư' : 'Thêm vật tư' ?></h1>
<form method="post" action="<?= e(url($action)) ?>" class="stacked-form card">
  <?= Csrf::field() ?>
  <label>Mã vật tư
    <input type="text" name="code" value="<?= e($isEdit ? $material['code'] : old($old, 'code')) ?>" required>
    <?= fieldError($errors, 'code') ?>
  </label>
  <label>Tên vật tư
    <input type="text" name="name" value="<?= e($isEdit ? $material['name'] : old($old, 'name')) ?>" required>
    <?= fieldError($errors, 'name') ?>
  </label>
  <label>Nhóm vật tư
    <select name="material_group_id" required>
      <option value="">— Chọn nhóm —</option>
      <?php foreach ($groups as $g): ?>
        <option value="<?= (int) $g['id'] ?>" <?= (int) $currentGroupId === (int) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?= fieldError($errors, 'material_group_id') ?>
  </label>
  <label>Đơn vị tính (ví dụ: cái, kg, g, cuộn)
    <input type="text" name="unit_of_measure" value="<?= e($isEdit ? $material['unit_of_measure'] : old($old, 'unit_of_measure')) ?>" required>
    <?= fieldError($errors, 'unit_of_measure') ?>
  </label>
  <label>Loại đơn vị
    <select name="unit_type" required>
      <option value="">— Chọn —</option>
      <option value="count" <?= $currentUnitType === 'count' ? 'selected' : '' ?>>Đếm được (hộp, tem, seal, thùng — làm tròn lên)</option>
      <option value="continuous" <?= $currentUnitType === 'continuous' ? 'selected' : '' ?>>Liên tục (gram cà phê — giữ số lẻ)</option>
    </select>
    <?= fieldError($errors, 'unit_type') ?>
  </label>
  <label>Ghi chú
    <textarea name="notes" rows="2"><?= e($isEdit ? ($material['notes'] ?? '') : old($old, 'notes')) ?></textarea>
  </label>
  <div>
    <button type="submit" class="btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo vật tư' ?></button>
    <a class="btn-secondary btn" href="<?= e(url('/danh-muc/vat-tu')) ?>">Hủy</a>
  </div>
</form>
