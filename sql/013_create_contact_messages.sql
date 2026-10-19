-- =====================================================================
-- 013 — Contact form messages
-- contact_handler.php stores every message here before emailing a copy
-- to CONTACT_TO; emailed_at stays NULL when that email failed, so no
-- message is lost. Safe to re-run.
-- =====================================================================

CREATE TABLE IF NOT EXISTS contact_messages (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    ip_address  VARCHAR(45) NULL,
    emailed_at  DATETIME NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
