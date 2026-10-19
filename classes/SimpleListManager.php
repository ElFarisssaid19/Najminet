<?php
declare(strict_types=1);

/**
 * SimpleListManager
 * --------------------------------------------------------------------
 * Generic CRUD for simple "list of records" tools that all share the
 * same shape: id, user_id, a handful of fields, created_at. Used by:
 * Employees, Products, Suppliers, Positions, Departments, Contract Types.
 *
 * The column whitelist is defined in PHP (never from user input), so
 * building the SQL from $this->columns is safe — the user can only
 * ever influence VALUES, never column/table names.
 *
 * Every method scopes by user_id, so one user can never read, edit, or
 * delete another user's records even if they guess an id.
 * --------------------------------------------------------------------
 */
class SimpleListManager
{
    private PDO $pdo;
    private string $table;
    private array $columns; // whitelist of editable column names for this table

    public function __construct(PDO $pdo, string $table, array $columns)
    {
        $this->pdo     = $pdo;
        $this->table   = $table;
        $this->columns = $columns;
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(int $userId, array $data): bool
    {
        $data   = $this->filterColumns($data);
        $fields = array_keys($data);

        $sql = "INSERT INTO {$this->table} (user_id, " . implode(', ', $fields) . ")
                VALUES (:user_id, " . implode(', ', array_map(fn($f) => ":$f", $fields)) . ")";

        $stmt   = $this->pdo->prepare($sql);
        $params = [':user_id' => $userId];
        foreach ($data as $k => $v) {
            $params[":$k"] = $v;
        }
        return $stmt->execute($params);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $data = $this->filterColumns($data);
        if (empty($data)) {
            return false;
        }

        $setClauses = implode(', ', array_map(fn($f) => "$f = :$f", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $setClauses WHERE id = :id AND user_id = :uid";

        $stmt   = $this->pdo->prepare($sql);
        $params = [':id' => $id, ':uid' => $userId];
        foreach ($data as $k => $v) {
            $params[":$k"] = $v;
        }
        return $stmt->execute($params);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id AND user_id = :uid");
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    /** Keeps only whitelisted columns — silently drops anything else the caller passed in. */
    private function filterColumns(array $data): array
    {
        return array_intersect_key($data, array_flip($this->columns));
    }
}
