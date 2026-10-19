-- =====================================================================
-- 011 — PayPal capture details on payments
-- billing/paypal-create-order.php records every order here (pending,
-- status CREATED) and billing/paypal-capture.php completes it. order_id
-- is already UNIQUE (006), so one PayPal order can never activate two
-- subscription periods. Safe to re-run.
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

CREATE PROCEDURE najminet_add_index(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN definition VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND INDEX_NAME = idx
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', tbl, '` ADD ', definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL najminet_add_column('payments', 'capture_id', 'VARCHAR(64) NULL DEFAULT NULL AFTER status');
CALL najminet_add_column('payments', 'completed_at', 'DATETIME NULL DEFAULT NULL AFTER capture_id');
CALL najminet_add_index('payments', 'uniq_payment_order', 'UNIQUE KEY uniq_payment_order (order_id)');

DROP PROCEDURE najminet_add_column;
DROP PROCEDURE najminet_add_index;
