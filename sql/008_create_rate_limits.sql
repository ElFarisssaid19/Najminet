-- =====================================================================
-- 008 — Generic rate-limit counters
--
-- One row per attempt. `action` names the protected operation
-- ('newsletter_subscribe', later 'login_ip' / 'login_email'), and
-- `identifier_hash` is the SHA-256 of the IP or email, so no address is
-- stored in clear text here.
--
-- NOTE: identical to database/migrations/rate_limits.sql. Keep in sync.
-- =====================================================================

CREATE TABLE IF NOT EXISTS rate_limits (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action          VARCHAR(40) NOT NULL,
    identifier_hash CHAR(64) NOT NULL,
    attempted_at    DATETIME NOT NULL,
    KEY idx_rate_lookup (action, identifier_hash, attempted_at),
    KEY idx_rate_cleanup (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
