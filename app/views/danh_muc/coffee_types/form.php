<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$isEdit = $coffeeType !== null;
$action = $isEdit ? '/danh-muc/loai-ca-phe/' . $coffeeType['id'] . '/sua' : '/danh-muc/loai-ca-phe/tao';
?>
<h1><?= $isEdit ? 'Sửa loại cà phê' : 'Thêm loại cà phê' ?></h1>
<form method="post" action="<?= e(url($action)) ?>" class="stacked-form card">
  <?= Csrf::field() ?>
  <label>Mã loại cà phê
    <input type="text" name="code" value="<?= e($isEdit ? $coffeeType['code'] : old($old, 'code')) ?>" required>
    <?= fieldError($errors, 'code') ?>
  </label>
  <label>Tên loại cà phê
    <input type="text" name="name" value="<?= e($isEdit ? $coffeeType['name'] : old($old, 'name')) ?>" required>
    <?= fieldError($errors, 'name') ?>
  </label>
  <div>
    <button type="submit" class="btn-primary"><?= $isEdit ? 'Lưu thay đổi' : 'Tạo loại cà phê' ?></button>
    <a class="btn-secondary btn" href="<?= e(url('/danh-muc/loai-ca-phe')) ?>">Hủy</a>
  </div>
</form>
