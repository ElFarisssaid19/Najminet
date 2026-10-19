-- =====================================================================
-- 009 — Foreign key companies.user_id → users.id
-- Deleting a user now deletes their company row too, like the auth
-- tables. Safe to re-run: the key is only added when it is missing.
--
-- If this fails with error 1452, some companies rows point to users that
-- no longer exist. List them, then delete them and run this file again:
--   SELECT c.id, c.user_id FROM companies c
--   LEFT JOIN users u ON u.id = c.user_id WHERE u.id IS NULL;
-- =====================================================================

DROP PROCEDURE IF EXISTS najminet_add_foreign_key;

DELIMITER $$
CREATE PROCEDURE najminet_add_foreign_key(IN tbl VARCHAR(64), IN fk VARCHAR(64), IN definition VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = tbl
          AND CONSTRAINT_NAME = fk AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', tbl, '` ADD CONSTRAINT `', fk, '` ', definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL najminet_add_foreign_key('companies', 'fk_company_user', 'FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');

DROP PROCEDURE najminet_add_foreign_key;
