<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$isEdit = $customer !== null;
$action = $isEdit ? '/danh-muc/khach-hang/' . $customer['id'] . '/sua' : '/danh-muc/khach-hang/tao';
?>
<h1><?= $isEdit ? 'Sửa khách hàng' : 'Thêm khách hàng' ?></h1>
<form method="post" action="<?= e(url($action)) ?>" class="stacked-form card">
  <?= Csrf::field() ?>
  <label>Mã khách hàng
    <input type="text" name="code" value="<?= e($isEdit ? $customer['code'] : old($old, 'code')) ?>" required>
    <?= fieldError($errors, 'code') ?>
  </label>
  <label>Tên khách hàng
    <input type="text" name="name" value="<?= e($isEdit ? $customer['name'] : old($old, 'name')) ?>" required>
    <?= fieldError($errors, 'name') ?>
  </label>
  <label>Thị trường
    <input type="text" name="market" value="<?= e($isEdit ? ($customer['market'] ?? '') : old($old, 'market')) ?>">
  </label>
  <div>
    <button type="submit" class="btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo khách hàng' ?></button>
    <a class="btn-secondary btn" href="<?= e(url('/danh-muc/khach-hang')) ?>">Hủy</a>
  </div>
</form>
