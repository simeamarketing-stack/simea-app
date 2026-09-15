<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
/**
 * Cần biến $advice (kết quả CatchUpAdvisor::advise()).
 * Phần chi phí chỉ hiện cho Quản lý — Vận hành chỉ thấy phần thao tác.
 */
$showCost = Auth::is(ROLE_QUAN_LY);
$rec = $advice['recommended'];
?>
<div class="advice">
  <div class="advice-head">
    <span class="badge badge-info">Đề xuất bù <?= formatQty($advice['shortfall'], 'count') ?> hộp cho ngày mai</span>
    <strong><?= e(CatchUpAdvisor::label($rec)) ?></strong>
  </div>
  <p class="hint"><?= e($advice['reason']) ?></p>

  <div class="advice-options">
    <div class="advice-option <?= $rec === 'overtime' ? 'advice-pick' : '' ?>">
      <div class="advice-option-head">
        Tăng ca với đội hiện tại
        <?php if ($rec === 'overtime'): ?><span class="badge badge-good">Nên chọn</span><?php endif; ?>
      </div>
      <?php if ($advice['overtime']['covers_all']): ?>
        <p>Giữ <strong><?= (int) $advice['overtime']['crew'] ?> người</strong>, làm thêm
           <strong><?= formatHours(round($advice['overtime']['hours_needed'], 1)) ?></strong>.</p>
      <?php else: ?>
        <p class="cell-risk">Không đủ: cần <?= formatHours(round($advice['overtime']['hours_needed'], 1)) ?>
           nhưng trần tăng ca là <?= (int) MAX_OVERTIME_HOURS ?> giờ/ngày.</p>
        <p>Tăng ca kịch trần chỉ bù được <?= formatQty($advice['overtime']['boxes_covered'], 'count') ?> hộp.</p>
      <?php endif; ?>
      <p class="hint"><?= formatQty(round($advice['overtime']['labor_hours'], 1)) ?> giờ công · làm được ngay, không cần tìm người</p>
      <?php if ($showCost): ?>
        <p class="advice-cost">Chi phí: <strong><?= formatMoney($advice['overtime']['cost']) ?></strong>
          <span class="hint">(lương &times; <?= rtrim(rtrim(number_format(OVERTIME_MULTIPLIER, 1, ',', '.'), '0'), ',') ?>)</span></p>
      <?php endif; ?>
    </div>

    <div class="advice-option <?= $rec === 'extra_workers' ? 'advice-pick' : '' ?>">
      <div class="advice-option-head">
        Thêm người cho ngày mai
        <?php if ($rec === 'extra_workers'): ?><span class="badge badge-good">Nên chọn</span><?php endif; ?>
      </div>
      <p>Bổ sung <strong><?= (int) $advice['extra_workers']['people'] ?> người</strong>, ca bình thường
         <?= formatHours($advice['extra_workers']['hours']) ?>.</p>
      <p class="hint"><?= formatQty(round($advice['extra_workers']['labor_hours'], 1)) ?> giờ công · phải bố trí được người trước ca</p>
      <?php if ($showCost): ?>
        <p class="advice-cost">Chi phí: <strong><?= formatMoney($advice['extra_workers']['cost']) ?></strong>
          <span class="hint">(lương giờ thường)</span></p>
      <?php endif; ?>
    </div>

    <?php if ($advice['combo']): ?>
    <div class="advice-option <?= $rec === 'combo' ? 'advice-pick' : '' ?>">
      <div class="advice-option-head">
        Kết hợp
        <?php if ($rec === 'combo'): ?><span class="badge badge-good">Nên chọn</span><?php endif; ?>
      </div>
      <p>Tăng ca kịch trần <?= formatHours($advice['combo']['overtime_hours']) ?> với
         <?= (int) $advice['combo']['crew'] ?> người, cộng thêm
         <strong><?= (int) $advice['combo']['people'] ?> người</strong>.</p>
      <?php if ($showCost): ?>
        <p class="advice-cost">Chi phí: <strong><?= formatMoney($advice['combo']['cost']) ?></strong></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <p class="hint advice-foot">
    Tính theo định mức: 1 người làm được <?= formatQty(round($advice['per_worker_rate'], 2)) ?> hộp/giờ.
    <?php if ($advice['crew_is_assumed']): ?>
      Ca vừa rồi chưa nhập số nhân sự nên đang lấy theo định mức <?= (int) $advice['crew'] ?> người.
    <?php endif; ?>
    Giả định sản lượng tỷ lệ thuận với số người — con số để tham khảo khi quyết định, không phải cam kết.
  </p>
</div>
