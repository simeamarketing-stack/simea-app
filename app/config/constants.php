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
