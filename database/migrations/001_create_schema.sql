-- ApotekCare — full schema (all phases created up front, §27/§58).
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- RBAC & USERS
-- ============================================================

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    module VARCHAR(60) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    photo VARCHAR(255) NULL,
    role_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_throttle (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL UNIQUE,
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at DATETIME NOT NULL,
    locked_until DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PHARMACY / SETTINGS
-- ============================================================

CREATE TABLE pharmacies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    logo VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    npwp VARCHAR(30) NULL,
    receipt_footer VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `group` VARCHAR(60) NOT NULL DEFAULT 'general',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE printer_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    paper_size ENUM('58mm','80mm') NOT NULL DEFAULT '80mm',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    auto_print TINYINT(1) NOT NULL DEFAULT 0,
    header_text VARCHAR(255) NULL,
    footer_text VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MASTER DATA
-- ============================================================

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medicine_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medicine_groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) NULL,
    requires_prescription TINYINT(1) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE units (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE manufacturers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE racks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE doctors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    sip_number VARCHAR(60) NULL,
    specialization VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_doctors_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_number VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    birth_date DATE NULL,
    gender ENUM('L','P') NULL,
    address VARCHAR(255) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_customers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    address VARCHAR(255) NULL,
    npwp VARCHAR(30) NULL,
    payment_term_days INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_suppliers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MEDICINES / INVENTORY
-- ============================================================

CREATE TABLE medicines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    barcode VARCHAR(50) NULL UNIQUE,
    name VARCHAR(180) NOT NULL,
    generic_name VARCHAR(180) NULL,
    brand_name VARCHAR(180) NULL,
    category_id INT UNSIGNED NULL,
    medicine_type_id INT UNSIGNED NULL,
    medicine_group_id INT UNSIGNED NULL,
    manufacturer_id INT UNSIGNED NULL,
    unit_id INT UNSIGNED NULL,
    large_unit_id INT UNSIGNED NULL,
    content_per_unit DECIMAL(10,2) NOT NULL DEFAULT 1,
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    selling_price_prescription DECIMAL(15,2) NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 0,
    maximum_stock INT NOT NULL DEFAULT 0,
    rack_id INT UNSIGNED NULL,
    description TEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    CONSTRAINT fk_medicines_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_medicines_type FOREIGN KEY (medicine_type_id) REFERENCES medicine_types(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_medicines_group FOREIGN KEY (medicine_group_id) REFERENCES medicine_groups(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_medicines_manufacturer FOREIGN KEY (manufacturer_id) REFERENCES manufacturers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_medicines_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_medicines_large_unit FOREIGN KEY (large_unit_id) REFERENCES units(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_medicines_rack FOREIGN KEY (rack_id) REFERENCES racks(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_medicines_name (name),
    INDEX idx_medicines_code (code),
    INDEX idx_medicines_barcode (barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE unit_conversions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    from_unit_id INT UNSIGNED NOT NULL,
    to_unit_id INT UNSIGNED NOT NULL,
    conversion_factor DECIMAL(12,4) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_uc_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_uc_from_unit FOREIGN KEY (from_unit_id) REFERENCES units(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_uc_to_unit FOREIGN KEY (to_unit_id) REFERENCES units(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medicine_barcodes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    barcode VARCHAR(50) NOT NULL UNIQUE,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mb_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_medicine_barcodes_barcode (barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE medicine_batches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    batch_number VARCHAR(60) NOT NULL,
    received_date DATE NOT NULL,
    production_date DATE NULL,
    expired_date DATE NOT NULL,
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    initial_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    available_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('active','expired','depleted','recalled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_batch_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_batch_number (batch_number),
    INDEX idx_batch_expired (expired_date),
    INDEX idx_batch_medicine_fefo (medicine_id, expired_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    movement_type ENUM('purchase','sale','sale_return','purchase_return','adjustment','stock_opname','damaged','expired','transfer','opening_balance') NOT NULL,
    reference_type VARCHAR(60) NULL,
    reference_id INT UNSIGNED NULL,
    qty_in DECIMAL(12,2) NOT NULL DEFAULT 0,
    qty_out DECIMAL(12,2) NOT NULL DEFAULT 0,
    balance_after DECIMAL(12,2) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sm_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sm_batch FOREIGN KEY (batch_id) REFERENCES medicine_batches(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_sm_medicine_created (medicine_id, created_at),
    INDEX idx_sm_batch (batch_id),
    INDEX idx_sm_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_opnames (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    opname_number VARCHAR(40) NOT NULL UNIQUE,
    opname_date DATE NOT NULL,
    status ENUM('draft','completed') NOT NULL DEFAULT 'draft',
    note VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_opname_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stock_opname_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_opname_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NOT NULL,
    system_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    physical_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    difference_qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    note VARCHAR(255) NULL,
    CONSTRAINT fk_sod_opname FOREIGN KEY (stock_opname_id) REFERENCES stock_opnames(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sod_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sod_batch FOREIGN KEY (batch_id) REFERENCES medicine_batches(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PURCHASING (schema ready now, UI ships Phase 3)
-- ============================================================

CREATE TABLE purchase_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(40) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED NOT NULL,
    order_date DATE NOT NULL,
    expected_date DATE NULL,
    status ENUM('draft','sent','partially_received','completed','cancelled') NOT NULL DEFAULT 'draft',
    notes VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_po_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_po_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_order_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_pod_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pod_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_number VARCHAR(40) NOT NULL UNIQUE,
    purchase_order_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NOT NULL,
    supplier_invoice_number VARCHAR(60) NULL,
    purchase_date DATE NOT NULL,
    due_date DATE NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax DECIMAL(15,2) NOT NULL DEFAULT 0,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    status ENUM('draft','received','cancelled') NOT NULL DEFAULT 'draft',
    notes VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchases_po FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_purchases_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_purchases_date (purchase_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    batch_number VARCHAR(60) NULL,
    expired_date DATE NULL,
    production_date DATE NULL,
    purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_pd_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pd_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pd_batch FOREIGN KEY (batch_id) REFERENCES medicine_batches(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_returns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_number VARCHAR(40) NOT NULL UNIQUE,
    purchase_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    return_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('draft','completed','cancelled') NOT NULL DEFAULT 'draft',
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pr_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pr_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_return_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_return_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_prd_return FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prd_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_prd_batch FOREIGN KEY (batch_id) REFERENCES medicine_batches(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(40) NOT NULL UNIQUE,
    supplier_id INT UNSIGNED NOT NULL,
    purchase_id INT UNSIGNED NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(40) NOT NULL DEFAULT 'cash',
    note VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sp_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sp_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PRESCRIPTIONS (schema ready now, UI ships Phase 4)
-- ============================================================

CREATE TABLE prescriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_number VARCHAR(40) NOT NULL UNIQUE,
    prescription_date DATE NOT NULL,
    customer_id INT UNSIGNED NULL,
    doctor_id INT UNSIGNED NULL,
    notes VARCHAR(255) NULL,
    status ENUM('draft','pending_verification','verified','processed','completed','cancelled') NOT NULL DEFAULT 'draft',
    input_by INT UNSIGNED NULL,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_presc_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_presc_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_presc_input_by FOREIGN KEY (input_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_presc_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE prescription_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    dosage VARCHAR(100) NULL,
    frequency VARCHAR(100) NULL,
    qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    usage_instructions VARCHAR(255) NULL,
    note VARCHAR(255) NULL,
    CONSTRAINT fk_pd2_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pd2_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SALES (schema ready now; seeded with sample rows for the dashboard;
-- POS UI ships Phase 2)
-- ============================================================

CREATE TABLE devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL UNIQUE,
    device_name VARCHAR(100) NULL,
    user_id INT UNSIGNED NULL,
    last_seen_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_devices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cash_shifts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_number VARCHAR(40) NOT NULL UNIQUE,
    cashier_id INT UNSIGNED NOT NULL,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    opening_at DATETIME NOT NULL,
    closing_balance_system DECIMAL(15,2) NULL,
    closing_balance_actual DECIMAL(15,2) NULL,
    difference DECIMAL(15,2) NULL,
    closing_at DATETIME NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    notes VARCHAR(255) NULL,
    CONSTRAINT fk_shift_cashier FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(40) NOT NULL UNIQUE,
    uuid CHAR(36) NOT NULL UNIQUE,
    transaction_date DATETIME NOT NULL,
    customer_id INT UNSIGNED NULL,
    doctor_id INT UNSIGNED NULL,
    prescription_id INT UNSIGNED NULL,
    cashier_id INT UNSIGNED NOT NULL,
    shift_id INT UNSIGNED NULL,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax DECIMAL(15,2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(15,2) NOT NULL DEFAULT 0,
    paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    change_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(40) NOT NULL DEFAULT 'cash',
    status ENUM('completed','refunded','cancelled') NOT NULL DEFAULT 'completed',
    notes VARCHAR(255) NULL,
    sync_status ENUM('synced','pending','conflict') NOT NULL DEFAULT 'synced',
    device_id VARCHAR(64) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sales_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sales_prescription FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_sales_cashier FOREIGN KEY (cashier_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sales_shift FOREIGN KEY (shift_id) REFERENCES cash_shifts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_sales_invoice (invoice_number),
    INDEX idx_sales_date (transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_sd_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sd_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sd_batch FOREIGN KEY (batch_id) REFERENCES medicine_batches(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    method VARCHAR(40) NOT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    reference_number VARCHAR(80) NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_returns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    return_number VARCHAR(40) NOT NULL UNIQUE,
    sale_id INT UNSIGNED NOT NULL,
    return_date DATE NOT NULL,
    reason VARCHAR(255) NULL,
    total DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('draft','completed','cancelled') NOT NULL DEFAULT 'draft',
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sr_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_sr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sale_return_details (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_return_id INT UNSIGNED NOT NULL,
    sale_detail_id INT UNSIGNED NULL,
    medicine_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NULL,
    qty DECIMAL(12,2) NOT NULL DEFAULT 0,
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    condition_note VARCHAR(255) NULL,
    CONSTRAINT fk_srd_return FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_srd_detail FOREIGN KEY (sale_detail_id) REFERENCES sale_details(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_srd_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_srd_batch FOREIGN KEY (batch_id) REFERENCES medicine_batches(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CASHIER / FINANCE (schema ready now; Cash shifts table defined above
-- because sales references it)
-- ============================================================

CREATE TABLE cash_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shift_id INT UNSIGNED NULL,
    type ENUM('cash_in','cash_out') NOT NULL,
    category VARCHAR(60) NOT NULL,
    reference_type VARCHAR(60) NULL,
    reference_id INT UNSIGNED NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    description VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ct_shift FOREIGN KEY (shift_id) REFERENCES cash_shifts(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_ct_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(40) NOT NULL UNIQUE,
    category VARCHAR(60) NOT NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    expense_date DATE NOT NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- AUDIT / SYNC / NOTIFICATIONS
-- ============================================================

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(40) NOT NULL,
    module VARCHAR(60) NOT NULL,
    record_id INT UNSIGNED NULL,
    old_data JSON NULL,
    new_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sync_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    device_id VARCHAR(64) NOT NULL,
    type VARCHAR(40) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending','synced','failed','conflict') NOT NULL DEFAULT 'pending',
    error_message VARCHAR(255) NULL,
    retry_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    INDEX idx_sync_status (status),
    INDEX idx_sync_device (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sync_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_queue_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL,
    message VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_synclog_queue FOREIGN KEY (sync_queue_id) REFERENCES sync_queue(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    type VARCHAR(40) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(255) NOT NULL,
    reference_type VARCHAR(60) NULL,
    reference_id INT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_notif_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
