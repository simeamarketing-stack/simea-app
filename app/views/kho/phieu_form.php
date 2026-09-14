<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<h1>Ghi nhận phiếu kho</h1>
<?php if (!empty($errors['lines'])): ?><div class="alert alert-error"><?= e($errors['lines']) ?></div><?php endif; ?>
<form method="post" action="<?= e(url('/kho/phieu/tao')) ?>" class="stacked-form card">
  <?= Csrf::field() ?>
  <label>Loại phiếu
    <select name="voucher_type" required>
      <option value="">— Chọn loại phiếu —</option>
      <option value="nhap">Nhập kho</option>
      <option value="tra">Trả kho (nhập lại)</option>
      <option value="hong">Hỏng / hao hụt (xuất khỏi kho)</option>
      <option value="dieu_chinh">Điều chỉnh (có thể âm hoặc dương)</option>
    </select>
    <?= fieldError($errors, 'voucher_type') ?>
  </label>
  <label>Số phiếu / PO (dùng chung cho cả phiếu)
    <input type="text" name="voucher_no">
  </label>
  <label>Ghi chú chung
    <textarea name="note" rows="2"></textarea>
  </label>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Vật tư</th><th>Số lượng</th></tr></thead>
    <tbody>
      <?php for ($i = 0; $i < 8; $i++): ?>
      <tr>
        <td>
          <select name="material_id[]">
            <option value="">— Chọn vật tư —</option>
            <?php foreach ($materials as $m): ?>
              <option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['group_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input type="number" step="0.0001" name="quantity[]"></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>
  </div>
  <p class="hint">Điều chỉnh có thể nhập số âm để trừ kho. Cần thêm dòng? Ghi nhận thêm 1 phiếu nữa.</p>

  <div>
    <button type="submit" class="btn-primary">Ghi nhận phiếu</button>
    <a class="btn-secondary btn" href="<?= e(url('/kho/ton-kho')) ?>">Hủy</a>
  </div>
</form>
