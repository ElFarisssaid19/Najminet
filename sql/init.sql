-- =====================================================================
-- NajmiNet — full database schema
--
-- Docker runs this file once, when the db_data volume is empty, and it
-- builds the complete current schema in one pass. To create it by hand:
--     mysql -u <user> -p <database> < sql/init.sql
--
-- Existing databases are upgraded with the numbered files in sql/
-- (000 … 014) instead. They are safe to re-run, and running them on a
-- database built from this file changes nothing. When the schema
-- changes, update this file AND add a new numbered migration.
-- =====================================================================

-- ---------------------------------------------------------------------
-- Users and subscription state (000, 001, 005, 006, 010, 014)
-- The subscription lives on the user row: plan, status, period and the
-- payment provider / order that activated it.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    full_name               VARCHAR(150) NOT NULL,
    company_name            VARCHAR(150) DEFAULT NULL,
    email                   VARCHAR(150) NOT NULL UNIQUE,
    password_hash           VARCHAR(255) NOT NULL,
    has_password            TINYINT(1) NOT NULL DEFAULT 0,      -- 1 once the user chose a password (register, reset); Google sign-in stores a random one
    oauth_provider          VARCHAR(20) NULL DEFAULT NULL,      -- 'google' for Google sign-in accounts
    avatar_url              VARCHAR(512) NULL DEFAULT NULL,
    plan                    VARCHAR(50) DEFAULT NULL,
    subscription_status     VARCHAR(20) DEFAULT NULL,
    subscription_start      DATETIME DEFAULT NULL,
    subscription_end        DATETIME DEFAULT NULL,
    trial_used              TINYINT(1) NOT NULL DEFAULT 0,      -- 1 once the account started its trial or paid (014)
    payment_provider        ENUM('stripe','paypal') NULL DEFAULT NULL,
    paypal_order_id         VARCHAR(64) NULL DEFAULT NULL,
    stripe_customer_id      VARCHAR(255) DEFAULT NULL,
    stripe_subscription_id  VARCHAR(255) DEFAULT NULL,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subscription_status (subscription_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Authentication: remember-me tokens, password resets, session versions (004)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_remember_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    selector CHAR(24) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    device_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME NULL,
    UNIQUE KEY uniq_remember_selector (selector),
    UNIQUE KEY uniq_remember_device (user_id, device_hash),
    KEY idx_remember_expiry (expires_at),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_reset_token_hash (token_hash),
    KEY idx_reset_user (user_id),
    KEY idx_reset_expiry (expires_at),
    CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_auth_versions (
    user_id INT PRIMARY KEY,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_auth_version_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Payments ledger (006, 011)
-- One row per provider order; order_id is unique so a single order can
-- never activate more than one subscription period.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payments (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    provider    VARCHAR(20) NOT NULL,            -- 'paypal'
    order_id    VARCHAR(64) NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    currency    CHAR(3) NOT NULL,                -- ISO 4217, e.g. 'USD'
    status      VARCHAR(30) NOT NULL,            -- CREATED (pending), COMPLETED, REJECTED
    capture_id  VARCHAR(64) NULL DEFAULT NULL,   -- PayPal capture that completed the order (011)
    completed_at DATETIME NULL DEFAULT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_payment_order (order_id),
    KEY idx_payment_user (user_id),
    CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Tools: Company / Reports / Balance sheet (002, 009)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS companies (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    user_id               INT NOT NULL,
    company_name          VARCHAR(150) NOT NULL,
    legal_name            VARCHAR(150) NULL,
    description           TEXT NULL,
    vat_number            VARCHAR(50) NULL,
    registration_number   VARCHAR(50) NULL,
    cie_number            VARCHAR(50) NULL,
    bank_name             VARCHAR(150) NULL,
    bank_account_number   VARCHAR(50) NULL,
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_company_per_user (user_id),
    CONSTRAINT fk_company_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expense_reports (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    report_year   SMALLINT NOT NULL,
    report_month  TINYINT NOT NULL,           -- 1 to 12
    amount        DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes         TEXT NULL,
    status        ENUM('draft', 'submitted') NOT NULL DEFAULT 'draft',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_report_month (user_id, report_year, report_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS balance_sheets (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL,
    sheet_year        SMALLINT NOT NULL,
    assets_data       JSON NULL,
    liabilities_data  JSON NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_sheet_per_year (user_id, sheet_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Tools: Employees, Products, Suppliers, Quotes, Invoices, TVA, Payroll,
-- Taxes, Documents, Admin reference data (003)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS employees (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    full_name     VARCHAR(150) NOT NULL,
    position      VARCHAR(100) NULL,
    department    VARCHAR(100) NULL,
    email         VARCHAR(150) NULL,
    phone         VARCHAR(30) NULL,
    salary        DECIMAL(12,2) NOT NULL DEFAULT 0,
    hire_date     DATE NULL,
    status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    name           VARCHAR(150) NOT NULL,
    reference      VARCHAR(60) NULL,
    category       VARCHAR(100) NULL,
    unit_price     DECIMAL(12,2) NOT NULL DEFAULT 0,
    unit           VARCHAR(30) NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS suppliers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    name            VARCHAR(150) NOT NULL,
    contact_person  VARCHAR(150) NULL,
    email           VARCHAR(150) NULL,
    phone           VARCHAR(30) NULL,
    address         VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    quote_number   VARCHAR(30) NOT NULL,
    client_name    VARCHAR(150) NOT NULL,
    items_data     JSON NULL,
    total_amount   DECIMAL(12,2) NOT NULL DEFAULT 0,
    status         ENUM('draft','sent','accepted','rejected') NOT NULL DEFAULT 'draft',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_quote_number (user_id, quote_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoices (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    invoice_number  VARCHAR(30) NOT NULL,
    client_name     VARCHAR(150) NOT NULL,
    issue_date      DATE NULL,
    due_date        DATE NULL,
    items_data      JSON NULL,
    total_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    status          ENUM('draft','pending','paid','overdue') NOT NULL DEFAULT 'draft',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_invoice_number (user_id, invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tva_declarations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    declaration_year    SMALLINT NOT NULL,
    quarter             TINYINT NOT NULL,        -- 1 to 4
    amount_collected    DECIMAL(12,2) NOT NULL DEFAULT 0,  -- TVA facturée
    amount_deductible   DECIMAL(12,2) NOT NULL DEFAULT 0,  -- TVA récupérable
    status              ENUM('draft','submitted') NOT NULL DEFAULT 'draft',
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tva_quarter (user_id, declaration_year, quarter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payroll_runs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    run_year       SMALLINT NOT NULL,
    run_month      TINYINT NOT NULL,          -- 1 to 12
    lines_data     JSON NULL,                 -- [{employee_id, employee_name, gross, cnss, ir, net}, ...]
    total_gross    DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_net      DECIMAL(12,2) NOT NULL DEFAULT 0,
    status         ENUM('draft','finalized') NOT NULL DEFAULT 'draft',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_payroll_month (user_id, run_year, run_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tax_brackets (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    bracket_type  ENUM('cnss','ir') NOT NULL,
    min_amount    DECIMAL(12,2) NOT NULL DEFAULT 0,
    max_amount    DECIMAL(12,2) NULL,          -- NULL = no upper bound
    rate          DECIMAL(5,2) NOT NULL DEFAULT 0,   -- percentage
    deduction     DECIMAL(12,2) NOT NULL DEFAULT 0,  -- monthly deduction, if applicable
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    title         VARCHAR(150) NOT NULL,
    category      VARCHAR(60) NULL,
    storage_key   VARCHAR(255) NOT NULL,        -- file name in the storage folder, outside the web root (012)
    file_size     INT NOT NULL DEFAULT 0,       -- bytes
    uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS positions (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    name         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS departments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    name         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contract_types (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    name         VARCHAR(150) NOT NULL,
    description  VARCHAR(255) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Newsletter subscribers, double opt-in (007)
-- Only token hashes are stored; the raw tokens exist only in the emailed
-- links, so a database leak cannot confirm or unsubscribe anyone.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email                  VARCHAR(190) NOT NULL,
    locale                 VARCHAR(5) NOT NULL DEFAULT 'en',
    status                 ENUM('pending','confirmed','unsubscribed') NOT NULL DEFAULT 'pending',
    confirm_token_hash     CHAR(64) NULL,
    unsubscribe_token_hash CHAR(64) NULL,
    token_expires_at       DATETIME NULL,
    ip_address             VARCHAR(45) NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at           DATETIME NULL,
    unsubscribed_at        DATETIME NULL,
    UNIQUE KEY uniq_newsletter_email (email),
    UNIQUE KEY uniq_newsletter_confirm_token (confirm_token_hash),
    UNIQUE KEY uniq_newsletter_unsub_token (unsubscribe_token_hash),
    KEY idx_newsletter_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Generic rate-limit counters (008)
-- One row per attempt; identifier_hash is the SHA-256 of the IP or email.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action          VARCHAR(40) NOT NULL,
    identifier_hash CHAR(64) NOT NULL,
    attempted_at    DATETIME NOT NULL,
    KEY idx_rate_lookup (action, identifier_hash, attempted_at),
    KEY idx_rate_cleanup (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Contact form messages (013)
-- Stored before the email copy to CONTACT_TO is sent; emailed_at stays
-- NULL when that email failed, so no message is lost.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    ip_address  VARCHAR(45) NULL,
    emailed_at  DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
