-- SIMEA Production Operations — core schema (MVP modules only)
-- IMPORTANT: every table must be InnoDB (transactions + row locking are
-- required for safe concurrent stock reservations). Some shared-hosting
-- phpMyAdmin defaults silently pick MyISAM — double check after import
-- with: SHOW TABLE STATUS WHERE Engine <> 'InnoDB';

SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- ============================================================
-- Auth
-- ============================================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role ENUM('quan_ly','van_hanh') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Danh mục (master data)
-- ============================================================

CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    market VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coffee_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fixed 8 groups, seeded. Editable only via direct DB access in this phase.
CREATE TABLE material_groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_group_id INT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,          -- normalized (trim + uppercase) by the app before insert
    name VARCHAR(150) NOT NULL,
    unit_of_measure VARCHAR(20) NOT NULL,       -- display unit, e.g. 'cái','kg','g','cuộn'
    unit_type ENUM('count','continuous') NOT NULL, -- drives centralized rounding (roundQuantity())
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (material_group_id) REFERENCES material_groups(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
    -- "Vỏ cup" and "nắp cup" are always two separate rows here — there is no
    -- shared-SKU linking column, by design, per the operating principle.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE skus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,           -- normalized trim + uppercase
    customer_id INT UNSIGNED NOT NULL,
    coffee_type_id INT UNSIGNED NOT NULL,
    units_per_box INT UNSIGNED NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (coffee_type_id) REFERENCES coffee_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- BOM & định mức năng suất
-- ============================================================

CREATE TABLE bom_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku_id INT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    status ENUM('draft','approved') NOT NULL DEFAULT 'draft',
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bom_sku_version (sku_id, version_number),
    FOREIGN KEY (sku_id) REFERENCES skus(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bom_lines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bom_version_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    basis_unit ENUM('per_box','per_cup') NOT NULL,
    quantity DECIMAL(12,4) NULL,     -- NULL = chưa xác nhận, never defaults to 0
    buffer_pct DECIMAL(5,2) NULL,    -- % dự phòng — separate field from waste
    waste_pct DECIMAL(5,2) NULL,     -- % hao hụt — separate field from buffer
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_bom_lines_version (bom_version_id),
    FOREIGN KEY (bom_version_id) REFERENCES bom_versions(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE case_specs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bom_version_id INT UNSIGNED NOT NULL UNIQUE,   -- 1:1 with the BOM version
    case_code VARCHAR(30) NULL,
    length_cm DECIMAL(6,2) NULL,
    width_cm DECIMAL(6,2) NULL,
    height_cm DECIMAL(6,2) NULL,
    boxes_per_case INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bom_version_id) REFERENCES bom_versions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE yield_norms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku_id INT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    status ENUM('draft','approved') NOT NULL DEFAULT 'draft',
    standard_worker_count INT UNSIGNED NULL,
    hours_per_day DECIMAL(4,2) NULL,
    boxes_per_hour DECIMAL(10,2) NULL,
    setup_time_minutes INT UNSIGNED NULL,
    qc_buffer_pct DECIMAL(5,2) NULL,
    approved_by INT UNSIGNED NULL,
    approved_at DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_yield_sku_version (sku_id, version_number),
    FOREIGN KEY (sku_id) REFERENCES skus(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Kho vật tư — append-only ledger + reservations
-- ============================================================

CREATE TABLE stock_vouchers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_type ENUM('nhap','tra','hong','dieu_chinh') NOT NULL,
    voucher_no VARCHAR(50) NULL,       -- shared PO/invoice reference for all lines
    note TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- APPEND-ONLY. Never UPDATE or DELETE a row here.
-- current_stock(material) = SUM(quantity) WHERE material_id = ?
CREATE TABLE stock_ledger (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    material_id INT UNSIGNED NOT NULL,
    transaction_type ENUM('nhap','tra','hong','dieu_chinh','xuat') NOT NULL,
    quantity DECIMAL(14,4) NOT NULL,    -- signed: + in, - out
    voucher_id INT UNSIGNED NULL,        -- set for nhap/tra/hong/dieu_chinh lines
    production_order_id INT UNSIGNED NULL, -- set only for 'xuat' lines
    reservation_id INT UNSIGNED NULL,    -- set only for 'xuat' lines
    note VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ledger_material (material_id),
    KEY idx_ledger_order (production_order_id),
    KEY idx_ledger_reservation (reservation_id),
    FOREIGN KEY (material_id) REFERENCES materials(id),
    FOREIGN KEY (voucher_id) REFERENCES stock_vouchers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE material_reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT UNSIGNED NOT NULL,
    material_id INT UNSIGNED NOT NULL,
    quantity_reserved DECIMAL(14,4) NOT NULL,
    status ENUM('active','released','consumed') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    released_by INT UNSIGNED NULL,
    released_at DATETIME NULL,
    released_reason VARCHAR(255) NULL,
    KEY idx_reservation_material_status (material_id, status),
    KEY idx_reservation_order (production_order_id),
    FOREIGN KEY (material_id) REFERENCES materials(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (released_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Lệnh sản xuất (hub)
-- ============================================================

CREATE TABLE production_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(40) NOT NULL UNIQUE,
    customer_id INT UNSIGNED NOT NULL,      -- only these two required at creation
    sku_id INT UNSIGNED NULL,
    bom_version_id INT UNSIGNED NULL,        -- mutable pointer while draft
    yield_norm_id INT UNSIGNED NULL,         -- mutable pointer while draft
    planned_quantity INT UNSIGNED NULL,
    planned_start_date DATE NULL,
    status ENUM('draft','released','in_progress','completed','cancelled') NOT NULL DEFAULT 'draft',

    released_bom_version_id INT UNSIGNED NULL,   -- FROZEN at release, never changes after
    released_yield_norm_id INT UNSIGNED NULL,    -- FROZEN at release, never changes after
    released_at DATETIME NULL,
    released_by INT UNSIGNED NULL,

    chuyen VARCHAR(50) NULL,
    phu_trach_user_id INT UNSIGNED NULL,

    original_due_date DATE NULL,    -- set ONCE at first schedule confirmation
    current_due_date DATE NULL,     -- mutable, updated on every reschedule
    schedule_confirmed_at DATETIME NULL,
    schedule_confirmed_by INT UNSIGNED NULL,

    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (sku_id) REFERENCES skus(id),
    FOREIGN KEY (bom_version_id) REFERENCES bom_versions(id),
    FOREIGN KEY (yield_norm_id) REFERENCES yield_norms(id),
    FOREIGN KEY (released_bom_version_id) REFERENCES bom_versions(id),
    FOREIGN KEY (released_yield_norm_id) REFERENCES yield_norms(id),
    FOREIGN KEY (released_by) REFERENCES users(id),
    FOREIGN KEY (phu_trach_user_id) REFERENCES users(id),
    FOREIGN KEY (schedule_confirmed_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- APPEND-ONLY
CREATE TABLE schedule_change_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT UNSIGNED NOT NULL,
    old_date DATE NULL,
    new_date DATE NOT NULL,
    reason TEXT NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_schedule_log_order (production_order_id),
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now that production_orders exists, wire it into the ledger/reservations FKs.
ALTER TABLE stock_ledger ADD FOREIGN KEY (production_order_id) REFERENCES production_orders(id);
ALTER TABLE stock_ledger ADD FOREIGN KEY (reservation_id) REFERENCES material_reservations(id);
ALTER TABLE material_reservations ADD FOREIGN KEY (production_order_id) REFERENCES production_orders(id);

-- ============================================================
-- Báo cáo ca
-- ============================================================

CREATE TABLE shift_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    production_order_id INT UNSIGNED NOT NULL,
    report_date DATE NOT NULL,
    line VARCHAR(50) NOT NULL,           -- chuyền
    output_qty INT UNSIGNED NULL,
    worker_count INT UNSIGNED NULL,
    incidents TEXT NULL,
    target_qty INT UNSIGNED NULL,         -- snapshot of expected output for this shift
    catch_up_target_tomorrow INT UNSIGNED NULL,  -- required if output_qty < target_qty
    remediation_plan TEXT NULL,                   -- required if output_qty < target_qty
    logged_by INT UNSIGNED NULL,
    logged_at DATETIME NULL,
    qc_confirmed_by INT UNSIGNED NULL,
    qc_confirmed_at DATETIME NULL,
    is_locked TINYINT(1) NOT NULL DEFAULT 0,
    locked_by INT UNSIGNED NULL,
    locked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_shift_report (production_order_id, report_date, line),
    FOREIGN KEY (production_order_id) REFERENCES production_orders(id),
    FOREIGN KEY (logged_by) REFERENCES users(id),
    FOREIGN KEY (qc_confirmed_by) REFERENCES users(id),
    FOREIGN KEY (locked_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- APPEND-ONLY, reason mandatory (enforced in app layer, NOT NULL here as a backstop)
CREATE TABLE shift_report_edit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_report_id INT UNSIGNED NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    reason TEXT NOT NULL,
    edited_by INT UNSIGNED NOT NULL,
    edited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_edit_log_report (shift_report_id),
    FOREIGN KEY (shift_report_id) REFERENCES shift_reports(id),
    FOREIGN KEY (edited_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Generic audit log (BOM approvals, master-data edits, release actions, ...)
-- ============================================================

CREATE TABLE audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    reason VARCHAR(255) NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_entity (entity_type, entity_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
