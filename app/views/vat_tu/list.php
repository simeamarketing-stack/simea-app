<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>Vật tư</h1>
  <?php if (Auth::is(ROLE_VAN_HANH)): ?>
    <a class="btn" href="<?= e(url('/danh-muc/vat-tu/tao')) ?>">+ Thêm vật tư</a>
  <?php endif; ?>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Mã</th><th>Tên vật tư</th><th>Nhóm</th><th>Đơn vị</th><th>Loại đơn vị</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($materials as $m): ?>
    <tr>
      <td><?= e($m['code']) ?></td>
      <td><?= e($m['name']) ?></td>
      <td><?= e($m['group_name']) ?></td>
      <td><?= e($m['unit_of_measure']) ?></td>
      <td><?= $m['unit_type'] === 'count' ? 'Đếm được (làm tròn lên)' : 'Liên tục (giữ số lẻ)' ?></td>
      <td><?php if (Auth::is(ROLE_VAN_HANH)): ?><a href="<?= e(url('/danh-muc/vat-tu/' . $m['id'] . '/sua')) ?>">Sửa</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$materials): ?><tr><td colspan="6">Chưa có vật tư nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
