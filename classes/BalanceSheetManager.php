<?php
declare(strict_types=1);

/**
 * BalanceSheetManager
 * --------------------------------------------------------------------
 * Handles the "Balance" tool: a simplified yearly balance sheet
 * (Actif / Passif) made of free-form line items, stored as JSON.
 *
 * Each line item looks like: { "label": "Cash", "amount": 12500.00 }
 * --------------------------------------------------------------------
 */
class BalanceSheetManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByYear(int $userId, int $year): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM balance_sheets WHERE user_id = :uid AND sheet_year = :year LIMIT 1"
        );
        $stmt->execute([':uid' => $userId, ':year' => $year]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['assets_data']      = json_decode($row['assets_data'] ?? '[]', true) ?: [];
        $row['liabilities_data'] = json_decode($row['liabilities_data'] ?? '[]', true) ?: [];
        return $row;
    }

    /**
     * $assets / $liabilities: arrays of ['label' => string, 'amount' => float]
     * Validates and sanitizes every row server-side before saving.
     */
    public function save(int $userId, int $year, array $assets, array $liabilities): bool
    {
        $cleanAssets      = $this->sanitizeLineItems($assets);
        $cleanLiabilities = $this->sanitizeLineItems($liabilities);

        $sql = "INSERT INTO balance_sheets (user_id, sheet_year, assets_data, liabilities_data)
                VALUES (:uid, :year, :assets, :liabilities)
                ON DUPLICATE KEY UPDATE
                    assets_data = VALUES(assets_data),
                    liabilities_data = VALUES(liabilities_data)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':uid'         => $userId,
            ':year'        => $year,
            ':assets'      => json_encode($cleanAssets, JSON_UNESCAPED_UNICODE),
            ':liabilities' => json_encode($cleanLiabilities, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Never trust raw client input: strip empty rows, cast amounts to
     * float, cap label length.
     */
    private function sanitizeLineItems(array $items): array
    {
        $clean = [];
        foreach ($items as $item) {
            $label  = trim((string) ($item['label'] ?? ''));
            $amount = (float) ($item['amount'] ?? 0);

            if ($label === '') {
                continue; // skip empty rows
            }

            $clean[] = [
                'label'  => mb_substr($label, 0, 150),
                'amount' => round($amount, 2),
            ];
        }
        return $clean;
    }

    public static function sumLineItems(array $items): float
    {
        return array_reduce($items, fn($carry, $item) => $carry + (float) ($item['amount'] ?? 0), 0.0);
    }
}
