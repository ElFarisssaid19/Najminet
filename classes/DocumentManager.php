<?php
declare(strict_types=1);

/**
 * DocumentManager
 * --------------------------------------------------------------------
 * Handles the "Documents" tool. Files are stored outside the web root
 * (defaultStorageDir()) under a random storage key, and `documents`
 * keeps the metadata and that key. They are served only by
 * document.php, to their owner.
 * --------------------------------------------------------------------
 */
class DocumentManager
{
    private PDO $pdo;
    private string $storageDir;

    private const MIME_TYPES = [
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];
    // What finfo may report for each extension. Office files are often
    // reported as their container format (ZIP for docx/xlsx, OLE for doc/xls).
    private const CONTENT_TYPES = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
    ];
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB
    private const STORAGE_KEY_PATTERN = '/\A[a-f0-9]{32}\.(pdf|docx?|xlsx?|png|jpe?g)\z/';

    public function __construct(PDO $pdo, ?string $storageDir = null)
    {
        $this->pdo        = $pdo;
        $this->storageDir = rtrim($storageDir ?? self::defaultStorageDir(), '/');
    }

    /**
     * Next to the project folder, never inside it: <parent>/storage/documents.
     * In Docker that is /var/www/storage/documents, the "documents" volume.
     */
    public static function defaultStorageDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/documents';
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE user_id = :uid ORDER BY uploaded_at DESC");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documents WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Validates and stores an uploaded file (from $_FILES['document']).
     * Returns an error message string on failure, or null on success.
     */
    public function upload(int $userId, string $title, string $category, array $file): ?string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload failed. Please try again.';
        }
        if ($file['size'] > self::MAX_SIZE_BYTES) {
            return 'File is too large (10 MB max).';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset(self::MIME_TYPES[$ext])) {
            return 'File type not allowed. Allowed: ' . implode(', ', array_keys(self::MIME_TYPES)) . '.';
        }
        $detected = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($detected, self::CONTENT_TYPES[$ext], true)) {
            return 'The file content does not match its .' . $ext . ' extension.';
        }

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0750, true);
        }

        // Random storage key — never trust the original filename for storage.
        $storageKey = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $this->storageDir . '/' . $storageKey;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return 'Could not save the uploaded file.';
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO documents (user_id, title, category, storage_key, file_size)
             VALUES (:uid, :title, :category, :key, :size)"
        );
        $stmt->execute([
            ':uid'      => $userId,
            ':title'    => mb_substr(trim($title), 0, 150),
            ':category' => $category ?: null,
            ':key'      => $storageKey,
            ':size'     => (int) $file['size'],
        ]);

        return null; // success
    }

    public function delete(int $id, int $userId): bool
    {
        $doc = $this->getById($id, $userId);
        if (!$doc) {
            return false;
        }

        $stmt = $this->pdo->prepare("DELETE FROM documents WHERE id = :id AND user_id = :uid");
        $deleted = $stmt->execute([':id' => $id, ':uid' => $userId]);

        if ($deleted) {
            $fullPath = $this->filePath($doc);
            if ($fullPath !== null) {
                @unlink($fullPath);
            }
        }
        return $deleted;
    }

    /** Absolute path of a document's file, or null when the key is invalid or the file is gone. */
    public function filePath(array $doc): ?string
    {
        $key = (string) ($doc['storage_key'] ?? '');
        if (preg_match(self::STORAGE_KEY_PATTERN, $key) !== 1) {
            return null;
        }
        $path = $this->storageDir . '/' . $key;

        return is_file($path) ? $path : null;
    }

    public static function mimeType(array $doc): string
    {
        $ext = strtolower(pathinfo((string) $doc['storage_key'], PATHINFO_EXTENSION));

        return self::MIME_TYPES[$ext] ?? 'application/octet-stream';
    }

    /** File name offered to the browser: the document title plus its extension. */
    public static function downloadName(array $doc): string
    {
        $ext = strtolower(pathinfo((string) $doc['storage_key'], PATHINFO_EXTENSION));
        $base = trim((string) preg_replace('/[^\p{L}\p{N} ._-]+/u', '', (string) $doc['title']), ' .') ?: 'document';

        return mb_substr($base, 0, 100) . '.' . $ext;
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
