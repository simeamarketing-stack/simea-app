<?php if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$isDraft = $version['status'] === 'draft';
$canEdit = $isDraft && Auth::is(ROLE_QUAN_LY);
?>
<div class="page-head">
  <h1>Định mức <?= e($version['sku_code']) ?> — v<?= (int) $version['version_number'] ?></h1>
  <?= $isDraft ? '<span class="badge badge-warn">Nháp</span>' : '<span class="badge badge-good">Đã duyệt</span>' ?>
</div>

<?php if ($isDraft && $missing): ?>
  <div class="alert alert-error"><strong>Chưa thể duyệt</strong> — còn thiếu: <?= e(implode(', ', $missing)) ?>.</div>
<?php endif; ?>

<div class="card">
<?php if ($canEdit): ?>
<form method="post" action="<?= e(url('/dinh-muc/' . $version['id'] . '/sua')) ?>" class="stacked-form">
  <?= Csrf::field() ?>
  <label>Số người chuẩn <input type="number" name="standard_worker_count" value="<?= e($version['standard_worker_count'] ?? '') ?>"></label>
  <label>Giờ/ngày <input type="number" step="0.1" name="hours_per_day" value="<?= e($version['hours_per_day'] ?? '') ?>"></label>
  <label>Hộp/giờ (đo thực tế) <input type="number" step="0.01" name="boxes_per_hour" value="<?= e($version['boxes_per_hour'] ?? '') ?>"></label>
  <label>Thời gian setup (phút) <input type="number" name="setup_time_minutes" value="<?= e($version['setup_time_minutes'] ?? '') ?>"></label>
  <label>% dự phòng QC <input type="number" step="0.01" name="qc_buffer_pct" value="<?= e($version['qc_buffer_pct'] ?? '') ?>"></label>
  <div><button type="submit" class="btn-primary">Lưu</button></div>
</form>
<?php else: ?>
  <p>Số người chuẩn: <?= displayValue($version['standard_worker_count']) ?></p>
  <p>Giờ/ngày: <?= displayValue($version['hours_per_day']) ?></p>
  <p>Hộp/giờ đo thực tế: <?= displayValue($version['boxes_per_hour']) ?></p>
  <p>Thời gian setup: <?= displayValue($version['setup_time_minutes'], ' phút') ?></p>
  <p>% dự phòng QC: <?= displayValue($version['qc_buffer_pct'], '%') ?></p>
<?php endif; ?>
</div>

<?php if ($isDraft && Auth::is(ROLE_QUAN_LY)): ?>
<form method="post" action="<?= e(url('/dinh-muc/' . $version['id'] . '/duyet')) ?>" onsubmit="return confirm('Duyệt định mức năng suất này?');">
  <?= Csrf::field() ?>
  <button type="submit" class="btn-primary">Duyệt định mức</button>
</form>
<?php endif; ?>
