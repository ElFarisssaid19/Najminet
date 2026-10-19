<?php
declare(strict_types=1);

/**
 * QuoteManager
 * --------------------------------------------------------------------
 * Handles the "Quote" tool. Line items are stored as JSON (same pattern
 * as balance_sheets): [{ "description": "...", "quantity": 2, "unit_price": 150.00 }]
 * --------------------------------------------------------------------
 */
class QuoteManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM quotes WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['items_data'] = json_decode($row['items_data'] ?? '[]', true) ?: [];
        }
        return $rows;
    }

    public function getById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM quotes WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['items_data'] = json_decode($row['items_data'] ?? '[]', true) ?: [];
        return $row;
    }

    /** Generates the next quote number for this user, e.g. DEV-0001 */
    public function nextQuoteNumber(int $userId): string
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM quotes WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $count = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        return 'DEV-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(int $userId, string $quoteNumber, string $clientName, array $items, string $status = 'draft'): bool
    {
        $cleanItems = $this->sanitizeItems($items);
        $total      = $this->sumItems($cleanItems);

        $stmt = $this->pdo->prepare(
            "INSERT INTO quotes (user_id, quote_number, client_name, items_data, total_amount, status)
             VALUES (:uid, :number, :client, :items, :total, :status)"
        );
        return $stmt->execute([
            ':uid'    => $userId,
            ':number' => $quoteNumber,
            ':client' => $clientName,
            ':items'  => json_encode($cleanItems, JSON_UNESCAPED_UNICODE),
            ':total'  => $total,
            ':status' => $status,
        ]);
    }

    public function update(int $id, int $userId, string $clientName, array $items, string $status): bool
    {
        $cleanItems = $this->sanitizeItems($items);
        $total      = $this->sumItems($cleanItems);

        $stmt = $this->pdo->prepare(
            "UPDATE quotes SET client_name = :client, items_data = :items, total_amount = :total, status = :status
             WHERE id = :id AND user_id = :uid"
        );
        return $stmt->execute([
            ':client' => $clientName,
            ':items'  => json_encode($cleanItems, JSON_UNESCAPED_UNICODE),
            ':total'  => $total,
            ':status' => $status,
            ':id'     => $id,
            ':uid'    => $userId,
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM quotes WHERE id = :id AND user_id = :uid");
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    private function sanitizeItems(array $items): array
    {
        $clean = [];
        foreach ($items as $item) {
            $desc = trim((string) ($item['description'] ?? ''));
            $qty  = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            if ($desc === '' || $qty <= 0) {
                continue;
            }
            $clean[] = [
                'description' => mb_substr($desc, 0, 200),
                'quantity'    => round($qty, 2),
                'unit_price'  => round($price, 2),
            ];
        }
        return $clean;
    }

    public function sumItems(array $items): float
    {
        return array_reduce($items, fn($carry, $i) => $carry + ((float) $i['quantity'] * (float) $i['unit_price']), 0.0);
    }
}
