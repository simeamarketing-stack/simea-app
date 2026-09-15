<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }

$groups = [
    'missing_qty' => ['Thiếu số lượng kế hoạch', 'Lệnh chưa nhập số lượng kế hoạch nên chưa tính được nhu cầu vật tư lẫn tiến độ.'],
    'missing_material' => ['Thiếu vật tư', 'Vật tư đã giữ chỗ chưa đủ so với BOM của lệnh — cần bổ sung hoặc giữ thêm trước khi chạy.'],
    'schedule_unconfirmed' => ['Chưa xác nhận lịch', 'Lệnh đã phát hành nhưng xưởng chưa xác nhận hạn giao lần đầu.'],
    'due_risk' => ['Nguy cơ trễ hạn', 'Đã tới sát hạn giao hiện hành mà lệnh chưa hoàn tất.'],
    'stale_report' => ['Thiếu cập nhật báo cáo ca', 'Lệnh đang chạy nhưng nhiều ngày chưa có báo cáo ca mới.'],
];
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn-secondary btn" href="<?= e(url($backUrl)) ?>">&larr; Về dashboard</a>
</div>

<?php if (!$attentionByKey): ?>
  <div class="card"><p class="hint">Không có lệnh nào cần chú ý — mọi thứ đang bình thường.</p></div>
<?php endif; ?>

<?php foreach ($groups as $key => [$label, $explain]): ?>
  <?php if (!empty($attentionByKey[$key])): ?>
    <div class="card">
      <h3><?= e($label) ?> (<?= count($attentionByKey[$key]) ?>)</h3>
      <p class="hint"><?= e($explain) ?></p>
      <div class="table-wrap">
      <table>
        <thead><tr><th>Mã lệnh</th><th>Khách hàng</th><th>SKU</th><th>SL kế hoạch</th><th>Hạn giao gốc</th><th>Hạn giao hiện hành</th></tr></thead>
        <tbody>
        <?php foreach ($attentionByKey[$key] as $o): ?>
          <tr>
            <td><a href="<?= e(url('/lenh-san-xuat/' . $o['id'])) ?>"><?= e($o['order_code']) ?></a></td>
            <td><?= e($o['customer_name']) ?></td>
            <td><?= e($o['sku_code'] ?? '') ?></td>
            <td><?= $o['planned_quantity'] !== null ? formatQty($o['planned_quantity'], 'count') : displayValue(null) ?></td>
            <td><?= displayValue($o['original_due_date']) ?></td>
            <td><?= displayValue($o['current_due_date']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  <?php endif; ?>
<?php endforeach; ?>
