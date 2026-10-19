-- =====================================================================
-- NajmiNet Innovations — Company / Reports / Balance Sheet tables
-- Run once against your existing database (phpMyAdmin > SQL tab).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. companies — "Company" tool (Edit Company Information)
--    One row per user for now (1 company per account).
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
    UNIQUE KEY uniq_company_per_user (user_id)
    -- The foreign key to users(id) is added by 009_add_companies_user_fk.sql.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. expense_reports — "Reports" tool (Monthly Expense Reports)
--    One row per user/year/month.
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 3. balance_sheets — "Balance" tool (simplified CGNC balance sheet)
--    Line items stored as JSON: [{ "label": "...", "amount": 1234.50 }, ...]
-- ---------------------------------------------------------------------
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
