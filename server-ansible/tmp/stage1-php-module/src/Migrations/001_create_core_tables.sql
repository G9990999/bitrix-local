-- ============================================================
-- Snipe-IT Standalone Module — Migration 001
-- Creates the core tables required for asset management.
-- Compatible with MySQL 8+ / PostgreSQL 13+ / SQLite 3+
-- ============================================================

-- Track applied migrations
CREATE TABLE IF NOT EXISTS migrations (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration  VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Companies
CREATE TABLE IF NOT EXISTS companies (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Locations
CREATE TABLE IF NOT EXISTS locations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    address     VARCHAR(255) NULL,
    city        VARCHAR(255) NULL,
    country     VARCHAR(255) NULL,
    parent_id   INT UNSIGNED NULL,
    currency    VARCHAR(10)  NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Departments
CREATE TABLE IF NOT EXISTS departments (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    company_id  INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name   VARCHAR(255) NULL,
    last_name    VARCHAR(255) NULL,
    username     VARCHAR(255) NOT NULL UNIQUE,
    email        VARCHAR(255) NULL,
    password     VARCHAR(255) NULL,
    company_id   INT UNSIGNED NULL,
    location_id  INT UNSIGNED NULL,
    department_id INT UNSIGNED NULL,
    employee_num VARCHAR(255) NULL,
    jobtitle     VARCHAR(255) NULL,
    phone        VARCHAR(35)  NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    deleted_at   TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Manufacturers
CREATE TABLE IF NOT EXISTS manufacturers (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    url        VARCHAR(255) NULL,
    support_url VARCHAR(255) NULL,
    support_email VARCHAR(255) NULL,
    support_phone VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    category_type   ENUM('asset','accessory','consumable','component','license') NOT NULL DEFAULT 'asset',
    require_acceptance TINYINT(1) DEFAULT 0,
    use_default_eula TINYINT(1) DEFAULT 0,
    eula_text       TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Depreciations
CREATE TABLE IF NOT EXISTS depreciations (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    months      INT          NULL,
    depreciation_type ENUM('straight-line','percent') DEFAULT 'straight-line',
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Status Labels
CREATE TABLE IF NOT EXISTS status_labels (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    deployable  TINYINT(1) DEFAULT 0,
    pending     TINYINT(1) DEFAULT 0,
    archived    TINYINT(1) DEFAULT 0,
    color       VARCHAR(10) NULL,
    notes       TEXT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Suppliers
CREATE TABLE IF NOT EXISTS suppliers (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    address      VARCHAR(255) NULL,
    city         VARCHAR(255) NULL,
    country      VARCHAR(255) NULL,
    phone        VARCHAR(35)  NULL,
    email        VARCHAR(255) NULL,
    url          VARCHAR(255) NULL,
    notes        TEXT NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    deleted_at   TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset Models
CREATE TABLE IF NOT EXISTS models (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255) NOT NULL,
    manufacturer_id  INT UNSIGNED NULL,
    category_id      INT UNSIGNED NULL,
    depreciation_id  INT UNSIGNED NULL,
    model_number     VARCHAR(255) NULL,
    eol              INT UNSIGNED NULL,
    notes            TEXT NULL,
    fieldset_id      INT UNSIGNED NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    deleted_at       TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assets (hardware)
CREATE TABLE IF NOT EXISTS assets (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255) NULL,
    asset_tag        VARCHAR(255) NULL UNIQUE,
    model_id         INT UNSIGNED NULL,
    serial           VARCHAR(255) NULL,
    purchase_date    DATE NULL,
    purchase_cost    DECIMAL(10,2) NULL,
    order_number     VARCHAR(255) NULL,
    assigned_to      INT UNSIGNED NULL,
    assigned_type    VARCHAR(255) NULL,
    notes            TEXT NULL,
    user_id          INT UNSIGNED NULL,
    status_id        INT UNSIGNED NULL,
    company_id       INT UNSIGNED NULL,
    location_id      INT UNSIGNED NULL,
    supplier_id      INT UNSIGNED NULL,
    warranty_months  VARCHAR(10)  NULL,
    requestable      TINYINT(1)   DEFAULT 0,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    deleted_at       TIMESTAMP NULL,
    INDEX idx_assets_assigned_to (assigned_to),
    INDEX idx_assets_model_id    (model_id),
    INDEX idx_assets_status_id   (status_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Licenses
CREATE TABLE IF NOT EXISTS licenses (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255) NOT NULL,
    serial           TEXT NULL,
    seats            INT UNSIGNED NULL,
    purchase_cost    DECIMAL(10,2) NULL,
    purchase_date    DATE NULL,
    expiration_date  DATE NULL,
    manufacturer_id  INT UNSIGNED NULL,
    supplier_id      INT UNSIGNED NULL,
    category_id      INT UNSIGNED NULL,
    company_id       INT UNSIGNED NULL,
    user_id          INT UNSIGNED NULL,
    notes            TEXT NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    deleted_at       TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- License Seats (individual seat assignments)
CREATE TABLE IF NOT EXISTS license_seats (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id  INT UNSIGNED NOT NULL,
    asset_id    INT UNSIGNED NULL,
    assigned_to INT UNSIGNED NULL,
    user_id     INT UNSIGNED NULL,
    notes       TEXT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,
    INDEX idx_license_seats_license_id (license_id),
    INDEX idx_license_seats_assigned_to (assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Accessories
CREATE TABLE IF NOT EXISTS accessories (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    category_id     INT UNSIGNED NULL,
    company_id      INT UNSIGNED NULL,
    location_id     INT UNSIGNED NULL,
    manufacturer_id INT UNSIGNED NULL,
    supplier_id     INT UNSIGNED NULL,
    qty             INT UNSIGNED NULL DEFAULT 0,
    min_amt         INT UNSIGNED NULL DEFAULT 0,
    purchase_date   DATE NULL,
    purchase_cost   DECIMAL(10,2) NULL,
    order_number    VARCHAR(255) NULL,
    model_number    VARCHAR(255) NULL,
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Accessory Checkouts
CREATE TABLE IF NOT EXISTS accessories_users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    accessory_id   INT UNSIGNED NOT NULL,
    assigned_to    INT UNSIGNED NULL,
    note           TEXT NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    deleted_at     TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Consumables
CREATE TABLE IF NOT EXISTS consumables (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    category_id     INT UNSIGNED NULL,
    company_id      INT UNSIGNED NULL,
    location_id     INT UNSIGNED NULL,
    manufacturer_id INT UNSIGNED NULL,
    supplier_id     INT UNSIGNED NULL,
    qty             INT UNSIGNED NULL DEFAULT 0,
    min_amt         INT UNSIGNED NULL DEFAULT 0,
    purchase_date   DATE NULL,
    purchase_cost   DECIMAL(10,2) NULL,
    order_number    VARCHAR(255) NULL,
    item_no         VARCHAR(255) NULL,
    notes           TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Asset Maintenances
CREATE TABLE IF NOT EXISTS asset_maintenances (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id                INT UNSIGNED NOT NULL,
    supplier_id             INT UNSIGNED NULL,
    asset_maintenance_type  VARCHAR(255) NULL,
    title                   VARCHAR(100) NOT NULL,
    is_warranty             TINYINT(1) DEFAULT 0,
    start_date              DATE NULL,
    completion_date         DATE NULL,
    cost                    DECIMAL(10,2) NULL,
    notes                   TEXT NULL,
    user_id                 INT UNSIGNED NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    INDEX idx_asset_maintenances_asset_id (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Action Log (audit trail)
CREATE TABLE IF NOT EXISTS action_logs (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NULL,
    action_type    VARCHAR(255) NOT NULL,
    target_id      INT UNSIGNED NULL,
    target_type    VARCHAR(255) NULL,
    item_id        INT UNSIGNED NULL,
    item_type      VARCHAR(255) NULL,
    note           TEXT NULL,
    log_meta       TEXT NULL,
    remote_ip      VARCHAR(40) NULL,
    user_agent     TEXT NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    INDEX idx_action_logs_target (target_id, target_type),
    INDEX idx_action_logs_item   (item_id, item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO migrations (migration) VALUES ('001_create_core_tables');
