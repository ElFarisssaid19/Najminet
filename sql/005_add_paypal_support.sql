-- =====================================================================
-- 005 — PayPal fields on users
-- billing/paypal-capture.php records the provider and order on the user.
-- Replaces migrations/add_paypal_support.sql, which altered a
-- "subscriptions" table that never existed: subscription state lives on
-- users. Safe to re-run: a column is only added when it is missing.
-- =====================================================================

DROP PROCEDURE IF EXISTS najminet_add_column;

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
DELIMITER ;

CALL najminet_add_column('users', 'payment_provider', 'ENUM(''stripe'',''paypal'') NULL DEFAULT NULL');
CALL najminet_add_column('users', 'paypal_order_id', 'VARCHAR(64) NULL DEFAULT NULL');

DROP PROCEDURE najminet_add_column;
