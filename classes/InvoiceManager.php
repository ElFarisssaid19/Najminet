<?php
declare(strict_types=1);

/**
 * InvoiceManager
 * --------------------------------------------------------------------
 * Handles the "Invoices" tool. Same JSON line-item pattern as
 * QuoteManager, plus issue/due dates and a payment-oriented status.
 * --------------------------------------------------------------------
 */
class InvoiceManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE user_id = :uid ORDER BY id DESC");
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['items_data'] = json_decode($row['items_data'] ?? '[]', true) ?: [];
        }
        return $rows;
    }

    public function getById(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM invoices WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['items_data'] = json_decode($row['items_data'] ?? '[]', true) ?: [];
        return $row;
    }

    public function nextInvoiceNumber(int $userId): string
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM invoices WHERE user_id = :uid");
        $stmt->execute([':uid' => $userId]);
        $count = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        return 'INV-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(int $userId, string $invoiceNumber, string $clientName, ?string $issueDate, ?string $dueDate, array $items, string $status = 'draft'): bool
    {
        $cleanItems = $this->sanitizeItems($items);
        $total      = $this->sumItems($cleanItems);

        $stmt = $this->pdo->prepare(
            "INSERT INTO invoices (user_id, invoice_number, client_name, issue_date, due_date, items_data, total_amount, status)
             VALUES (:uid, :number, :client, :issue, :due, :items, :total, :status)"
        );
        return $stmt->execute([
            ':uid'    => $userId,
            ':number' => $invoiceNumber,
            ':client' => $clientName,
            ':issue'  => $issueDate ?: null,
            ':due'    => $dueDate ?: null,
            ':items'  => json_encode($cleanItems, JSON_UNESCAPED_UNICODE),
            ':total'  => $total,
            ':status' => $status,
        ]);
    }

    public function update(int $id, int $userId, string $clientName, ?string $issueDate, ?string $dueDate, array $items, string $status): bool
    {
        $cleanItems = $this->sanitizeItems($items);
        $total      = $this->sumItems($cleanItems);

        $stmt = $this->pdo->prepare(
            "UPDATE invoices SET client_name = :client, issue_date = :issue, due_date = :due,
                items_data = :items, total_amount = :total, status = :status
             WHERE id = :id AND user_id = :uid"
        );
        return $stmt->execute([
            ':client' => $clientName,
            ':issue'  => $issueDate ?: null,
            ':due'    => $dueDate ?: null,
            ':items'  => json_encode($cleanItems, JSON_UNESCAPED_UNICODE),
            ':total'  => $total,
            ':status' => $status,
            ':id'     => $id,
            ':uid'    => $userId,
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM invoices WHERE id = :id AND user_id = :uid");
        return $stmt->execute([':id' => $id, ':uid' => $userId]);
    }

    private function sanitizeItems(array $items): array
    {
        $clean = [];
        foreach ($items as $item) {
            $desc  = trim((string) ($item['description'] ?? ''));
            $qty   = (float) ($item['quantity'] ?? 0);
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
