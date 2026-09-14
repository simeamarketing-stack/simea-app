<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

define('ROLE_LANH_DAO', 'lanh_dao');
define('ROLE_DIEU_PHOI', 'dieu_phoi');
define('ROLE_XUONG', 'xuong');
define('ROLE_KHO', 'kho');
define('ROLE_QC', 'qc');

define('ALL_ROLES', [ROLE_LANH_DAO, ROLE_DIEU_PHOI, ROLE_XUONG, ROLE_KHO, ROLE_QC]);

define('ROLE_LABELS', [
    ROLE_LANH_DAO => 'Lãnh đạo',
    ROLE_DIEU_PHOI => 'Điều phối',
    ROLE_XUONG => 'Xưởng',
    ROLE_KHO => 'Kho',
    ROLE_QC => 'QC',
]);
