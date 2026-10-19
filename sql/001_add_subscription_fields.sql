-- =====================================================================
-- 001 — Subscription fields on users
-- For databases created before subscription support was added. Safe to
-- re-run: a column or index is only added when it is missing.
-- MySQL 8.0 has no "ADD COLUMN IF NOT EXISTS", hence the helper procedures.
-- =====================================================================

DROP PROCEDURE IF EXISTS najminet_add_column;
DROP PROCEDURE IF EXISTS najminet_add_index;

DELIMITER $$
CREATE PROCEDURE najminet_add_column(IN tbl VARCHAR(64), IN col VARCHAR(64), IN definition VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE PROCEDURE najminet_add_index(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN cols VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND INDEX_NAME = idx
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', tbl, '` ADD INDEX `', idx, '` (', cols, ')');
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL najminet_add_column('users', 'plan', 'VARCHAR(50) NULL DEFAULT NULL');
CALL najminet_add_column('users', 'subscription_status', 'VARCHAR(20) NULL DEFAULT NULL');
CALL najminet_add_column('users', 'subscription_start', 'DATETIME NULL DEFAULT NULL');
CALL najminet_add_column('users', 'subscription_end', 'DATETIME NULL DEFAULT NULL');
CALL najminet_add_column('users', 'trial_used', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL najminet_add_column('users', 'stripe_customer_id', 'VARCHAR(255) NULL DEFAULT NULL');
CALL najminet_add_column('users', 'stripe_subscription_id', 'VARCHAR(255) NULL DEFAULT NULL');
CALL najminet_add_index('users', 'idx_subscription_status', 'subscription_status');

DROP PROCEDURE najminet_add_column;
DROP PROCEDURE najminet_add_index;
