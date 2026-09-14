<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$isDraft = $version['status'] === 'draft';
$canEdit = $isDraft && Auth::is(ROLE_DIEU_PHOI);
?>
<div class="page-head">
  <h1>BOM <?= e($version['sku_code']) ?> — v<?= (int) $version['version_number'] ?></h1>
  <div>
    <?= $isDraft ? '<span class="badge badge-warn">Nháp</span>' : '<span class="badge badge-good">Đã duyệt</span>' ?>
  </div>
</div>

<?php if ($isDraft && $missing): ?>
  <div class="alert alert-error">
    <strong>Chưa thể duyệt — còn thiếu:</strong>
    <ul>
      <?php foreach ($missing as $issue): ?>
        <li><?= e($issue['material_name']) ?>: thiếu <?= e(implode(', ', $issue['missing'])) ?></li>
      <?php endforeach; ?>
      <?php if (!$caseSpecComplete): ?><li>Quy cách thùng chưa đầy đủ (mã thùng, số hộp/thùng)</li><?php endif; ?>
    </ul>
  </div>
<?php elseif ($isDraft && !$caseSpecComplete): ?>
  <div class="alert alert-error">Chưa thể duyệt — quy cách thùng chưa đầy đủ (mã thùng, số hộp/thùng).</div>
<?php endif; ?>

<div class="card">
<h3>Dòng vật tư</h3>
<div class="table-wrap">
<table>
  <thead><tr><th>Vật tư</th><th>Cơ sở tính</th><th>Định lượng</th><th>% dự phòng</th><th>% hao hụt</th><?php if ($canEdit): ?><th></th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($lines as $line): ?>
    <tr>
      <td><?= e($line['material_name']) ?> (<?= e($line['unit_of_measure']) ?>)</td>
      <td><?= $line['basis_unit'] === 'per_box' ? 'Theo hộp' : 'Theo cup' ?></td>
      <?php if ($canEdit): ?>
        <td colspan="3">
          <form method="post" action="<?= e(url('/bom/line/' . $line['id'] . '/sua')) ?>" class="inline-line-form">
            <?= Csrf::field() ?>
            <input type="number" step="0.0001" name="quantity" value="<?= e($line['quantity'] ?? '') ?>" placeholder="Định lượng">
            <input type="number" step="0.01" name="buffer_pct" value="<?= e($line['buffer_pct'] ?? '') ?>" placeholder="% dự phòng">
            <input type="number" step="0.01" name="waste_pct" value="<?= e($line['waste_pct'] ?? '') ?>" placeholder="% hao hụt">
            <button type="submit" class="btn-secondary btn">Lưu</button>
          </form>
        </td>
        <td>
          <form method="post" action="<?= e(url('/bom/line/' . $line['id'] . '/xoa')) ?>" onsubmit="return confirm('Xóa dòng này?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-secondary btn">Xóa</button>
          </form>
        </td>
      <?php else: ?>
        <td><?= displayValue($line['quantity']) ?></td>
        <td><?= displayValue($line['buffer_pct'], '%') ?></td>
        <td><?= displayValue($line['waste_pct'], '%') ?></td>
      <?php endif; ?>
    </tr>
  <?php endforeach; ?>
  <?php if (!$lines): ?><tr><td colspan="6">Chưa có dòng vật tư nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php if ($canEdit): ?>
<form method="post" action="<?= e(url('/bom/' . $version['id'] . '/dong')) ?>" class="stacked-form" style="margin-top:14px">
  <?= Csrf::field() ?>
  <div class="inline-line-form">
    <select name="material_id" required>
      <option value="">— Thêm vật tư —</option>
      <?php foreach ($materials as $m): ?>
        <option value="<?= (int) $m['id'] ?>"><?= e($m['name']) ?> (<?= e($m['group_name']) ?>)</option>
      <?php endforeach; ?>
    </select>
    <select name="basis_unit">
      <option value="per_box">Theo hộp</option>
      <option value="per_cup">Theo cup</option>
    </select>
    <button type="submit" class="btn">+ Thêm dòng</button>
  </div>
</form>
<?php endif; ?>
</div>

<div class="card">
<h3>Quy cách thùng</h3>
<?php if ($canEdit): ?>
<form method="post" action="<?= e(url('/bom/' . $version['id'] . '/case-spec')) ?>" class="stacked-form">
  <?= Csrf::field() ?>
  <label>Mã thùng <input type="text" name="case_code" value="<?= e($caseSpec['case_code'] ?? '') ?>"></label>
  <label>Dài (cm) <input type="number" step="0.01" name="length_cm" value="<?= e($caseSpec['length_cm'] ?? '') ?>"></label>
  <label>Rộng (cm) <input type="number" step="0.01" name="width_cm" value="<?= e($caseSpec['width_cm'] ?? '') ?>"></label>
  <label>Cao (cm) <input type="number" step="0.01" name="height_cm" value="<?= e($caseSpec['height_cm'] ?? '') ?>"></label>
  <label>Số hộp/thùng <input type="number" name="boxes_per_case" value="<?= e($caseSpec['boxes_per_case'] ?? '') ?>"></label>
  <div><button type="submit" class="btn-primary">Lưu quy cách thùng</button></div>
</form>
<?php else: ?>
  <p>Mã thùng: <?= displayValue($caseSpec['case_code'] ?? null) ?> · Số hộp/thùng: <?= displayValue($caseSpec['boxes_per_case'] ?? null) ?></p>
  <p>Kích thước: <?= displayValue($caseSpec['length_cm'] ?? null) ?> x <?= displayValue($caseSpec['width_cm'] ?? null) ?> x <?= displayValue($caseSpec['height_cm'] ?? null) ?> cm</p>
<?php endif; ?>
</div>

<?php if ($isDraft && Auth::is(ROLE_DIEU_PHOI)): ?>
<form method="post" action="<?= e(url('/bom/' . $version['id'] . '/duyet')) ?>" onsubmit="return confirm('Duyệt phiên bản BOM này?');">
  <?= Csrf::field() ?>
  <button type="submit" class="btn-primary">Duyệt BOM</button>
</form>
<?php endif; ?>
