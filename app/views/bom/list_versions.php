<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; } ?>
<div class="page-head">
  <h1>BOM — <?= e($sku['code']) ?></h1>
  <div>
    <a class="btn-secondary btn" href="<?= e(url('/dinh-muc/sku/' . $sku['id'])) ?>">Định mức năng suất</a>
    <?php if (Auth::is(ROLE_QUAN_LY)): ?>
      <form method="post" action="<?= e(url('/bom/sku/' . $sku['id'] . '/tao-phien-ban-moi')) ?>" style="display:inline">
        <?= Csrf::field() ?>
        <button type="submit" class="btn">+ Tạo phiên bản mới</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<div class="table-wrap">
<table>
  <thead><tr><th>Phiên bản</th><th>Trạng thái</th><th>Duyệt bởi</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($versions as $v): ?>
    <tr>
      <td>v<?= (int) $v['version_number'] ?></td>
      <td><?= $v['status'] === 'approved' ? '<span class="badge badge-good">Đã duyệt</span>' : '<span class="badge badge-warn">Nháp</span>' ?></td>
      <td><?= e($v['approved_by_name'] ?? '') ?></td>
      <td><a href="<?= e(url('/bom/' . $v['id'])) ?>">Xem</a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$versions): ?><tr><td colspan="4">Chưa có phiên bản BOM nào.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
