<?php
declare(strict_types=1);

/**
 * TvaManager
 * --------------------------------------------------------------------
 * Handles the "TVA" tool: one declaration per user/year/quarter.
 * net_due = amount_collected - amount_deductible (never negative in
 * the simplified version — credit carry-forward is a future refinement).
 * --------------------------------------------------------------------
 */
class TvaManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Returns an array indexed 1..4 with each quarter's declaration (or null). */
    public function getYear(int $userId, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT quarter, amount_collected, amount_deductible, status
             FROM tva_declarations WHERE user_id = :uid AND declaration_year = :year"
        );
        $stmt->execute([':uid' => $userId, ':year' => $year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quarters = array_fill(1, 4, null);
        foreach ($rows as $row) {
            $quarters[(int) $row['quarter']] = $row;
        }
        return $quarters;
    }

    public function saveQuarter(int $userId, int $year, int $quarter, float $collected, float $deductible, string $status = 'draft'): bool
    {
        $sql = "INSERT INTO tva_declarations (user_id, declaration_year, quarter, amount_collected, amount_deductible, status)
                VALUES (:uid, :year, :quarter, :collected, :deductible, :status)
                ON DUPLICATE KEY UPDATE
                    amount_collected = VALUES(amount_collected),
                    amount_deductible = VALUES(amount_deductible),
                    status = VALUES(status)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uid'        => $userId,
            ':year'       => $year,
            ':quarter'    => $quarter,
            ':collected'  => $collected,
            ':deductible' => $deductible,
            ':status'     => $status,
        ]);
    }

    public static function netDue(float $collected, float $deductible): float
    {
        return max(0.0, round($collected - $deductible, 2));
    }
}
