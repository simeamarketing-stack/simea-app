<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

// Only 2 roles: the same person currently covers Kho + Xưởng + QC day to day,
// and another covers Điều phối + Lãnh đạo — so permissions are merged
// accordingly rather than modeling 5 people who don't exist yet.
define('ROLE_QUAN_LY', 'quan_ly');   // was: Điều phối + Lãnh đạo
define('ROLE_VAN_HANH', 'van_hanh'); // was: Kho + Xưởng + QC

define('ALL_ROLES', [ROLE_QUAN_LY, ROLE_VAN_HANH]);

define('ROLE_LABELS', [
    ROLE_QUAN_LY => 'Quản lý',
    ROLE_VAN_HANH => 'Vận hành',
]);
