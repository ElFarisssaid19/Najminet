-- =====================================================================
-- 010 — users.has_password
-- Google sign-in stores a random password hash, so password_hash alone
-- cannot tell whether the user ever chose a password. The app now sets
-- has_password = 1 on register and on password reset.
--
-- Existing rows are filled from the best available data, once, when the
-- column is created (a re-run never overwrites flags set by the app):
--   1  oauth_provider IS NULL      never used Google: a password account
--   1  company_name IS NOT NULL    created by the register form, which
--                                  always stores a string there and a
--                                  chosen password; Google sign-in never
--                                  sets company_name
--   0  everything else             created through Google sign-in
--
-- Ambiguous: a Google-created account whose session version was bumped
-- (user_auth_versions.version > 1) either completed a password reset
-- (real password) or used "log out from all devices". Those stay 0, so
-- the register page tells them to sign in with Google; they can still
-- reset their password. List them with:
--   SELECT u.id, u.email FROM users u
--   JOIN user_auth_versions v ON v.user_id = u.id
--   WHERE u.has_password = 0 AND v.version > 1;
-- =====================================================================

DROP PROCEDURE IF EXISTS najminet_add_has_password;

DELIMITER $$
CREATE PROCEDURE najminet_add_has_password()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'has_password'
    ) THEN
        ALTER TABLE users ADD COLUMN has_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;
        UPDATE users SET has_password = 1 WHERE oauth_provider IS NULL OR company_name IS NOT NULL;
    END IF;
END$$
DELIMITER ;

CALL najminet_add_has_password();

DROP PROCEDURE najminet_add_has_password;
