-- =====================================================================
-- 012 — documents.file_path → documents.storage_key
-- Documents are now stored outside the web root and served only by
-- document.php, so the table keeps a storage key (the random file name)
-- instead of a web path. Existing rows lose their "uploads/documents/"
-- prefix. Runs once: nothing happens when storage_key already exists.
--
-- On Docker, move the files too: from the old "uploads" volume
-- (documents/ folder) into the new "documents" volume (see README).
-- =====================================================================

DROP PROCEDURE IF EXISTS najminet_documents_storage_key;

DELIMITER $$
CREATE PROCEDURE najminet_documents_storage_key()
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'file_path'
    ) THEN
        ALTER TABLE documents CHANGE COLUMN file_path storage_key VARCHAR(255) NOT NULL;
        UPDATE documents SET storage_key = SUBSTRING_INDEX(storage_key, '/', -1);
    END IF;
END$$
DELIMITER ;

CALL najminet_documents_storage_key();

DROP PROCEDURE najminet_documents_storage_key;
