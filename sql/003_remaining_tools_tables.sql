-- =====================================================================
-- NajmiNet Innovations — Remaining tools tables
-- Employees, Payroll, Quote, Products, Suppliers, Invoices, TVA,
-- Documents, Taxes, Admin (Positions / Departments / Contract Types)
-- Run once against your existing database.
-- =====================================================================

-- ---------------------------------------------------------------------
-- Employees
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

-- ---------------------------------------------------------------------
-- Products
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- Suppliers
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- Quotes (Devis) — line items stored as JSON, same pattern as balance_sheets
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- Invoices — line items stored as JSON
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- TVA declarations — one row per user/year/quarter
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- Payroll — one row per user/year/month, employee lines stored as JSON
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- Tax brackets (CNSS / IR configuration) — "Taxes" tool
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- Documents — simple file manager
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documents (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    title         VARCHAR(150) NOT NULL,
    category      VARCHAR(60) NULL,
    file_path     VARCHAR(255) NOT NULL,
    file_size     INT NOT NULL DEFAULT 0,       -- bytes
    uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Admin: Positions / Departments / Contract Types
-- ---------------------------------------------------------------------
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
