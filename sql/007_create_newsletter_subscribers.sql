-- =====================================================================
-- 007 — Newsletter subscribers (double opt-in)
--
-- Only token HASHES are stored: the raw tokens live in the emailed links
-- and nowhere else, so a database leak cannot be used to confirm or
-- unsubscribe anyone.
--
-- NOTE: identical to database/migrations/newsletter_subscribers.sql.
-- Keep the two in sync — this copy is what a fresh Docker volume runs.
-- =====================================================================

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
