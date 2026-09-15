<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

// Days before current_due_date at which a production order is flagged
// "nguy cơ trễ hạn" on the Lệnh sản xuất list.
define('DUE_DATE_RISK_DAYS', 2);

// If a production order has no shift report logged within this many days,
// it is flagged "thiếu cập nhật báo cáo".
define('REPORT_STALE_DAYS', 2);

// ---- Nhân công: dùng cho phần đề xuất bù sản lượng khi ca hụt chỉ tiêu ----
// Sửa trực tiếp ở đây khi mức lương thay đổi.

/** Lương một giờ công của công nhân xưởng (VNĐ). */
define('HOURLY_WAGE_VND', 30000);

/** Hệ số lương tăng ca (Bộ luật Lao động: tối thiểu 150% vào ngày thường). */
define('OVERTIME_MULTIPLIER', 1.5);

/** Hệ số lương cho người bổ sung — 1.0 = trả bằng lương giờ thường. */
define('EXTRA_WORKER_MULTIPLIER', 1.0);

/** Trần tăng ca mỗi ngày (giờ). Vượt mức này hệ thống sẽ báo phải thêm người. */
define('MAX_OVERTIME_HOURS', 4);

/** Cửa sổ ngày mà một ca hụt chỉ tiêu còn được coi là cần xử lý. */
define('SHORTFALL_WINDOW_DAYS', 7);
