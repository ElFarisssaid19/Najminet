-- =====================================================================
-- 006 — Google sign-in columns + payments ledger
--   users.oauth_provider / users.avatar_url  → written by auth/oauth/google.php
--   payments → one row per provider order; order_id is unique so a single
--              order can never activate more than one subscription period.
-- Safe to re-run.
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

CALL najminet_add_column('users', 'oauth_provider', 'VARCHAR(20) NULL DEFAULT NULL');
CALL najminet_add_column('users', 'avatar_url', 'VARCHAR(512) NULL DEFAULT NULL');

DROP PROCEDURE najminet_add_column;

CREATE TABLE IF NOT EXISTS payments (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    provider    VARCHAR(20) NOT NULL,            -- 'paypal'
    order_id    VARCHAR(64) NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    currency    CHAR(3) NOT NULL,                -- ISO 4217, e.g. 'USD'
    status      VARCHAR(30) NOT NULL,            -- provider order status: CREATED, COMPLETED, …
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_payment_order (order_id),
    KEY idx_payment_user (user_id),
    CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
