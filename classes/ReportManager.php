<?php
declare(strict_types=1);

/**
 * ReportManager
 * --------------------------------------------------------------------
 * Handles the "Reports" tool: one monthly expense report per user/year/month.
 * --------------------------------------------------------------------
 */
class ReportManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns an array indexed 1..12 with each month's report data
     * (or null if that month has no report yet).
     */
    public function getYear(int $userId, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT report_month, amount, notes, status
             FROM expense_reports
             WHERE user_id = :uid AND report_year = :year"
        );
        $stmt->execute([':uid' => $userId, ':year' => $year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $months = array_fill(1, 12, null);
        foreach ($rows as $row) {
            $months[(int) $row['report_month']] = $row;
        }
        return $months;
    }

    public function getMonth(int $userId, int $year, int $month): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM expense_reports
             WHERE user_id = :uid AND report_year = :year AND report_month = :month
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId, ':year' => $year, ':month' => $month]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Creates or updates the report for one month (upsert).
     */
    public function saveMonth(int $userId, int $year, int $month, float $amount, ?string $notes, string $status = 'draft'): bool
    {
        $sql = "INSERT INTO expense_reports (user_id, report_year, report_month, amount, notes, status)
                VALUES (:uid, :year, :month, :amount, :notes, :status)
                ON DUPLICATE KEY UPDATE
                    amount = VALUES(amount),
                    notes = VALUES(notes),
                    status = VALUES(status)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':uid'    => $userId,
            ':year'   => $year,
            ':month'  => $month,
            ':amount' => $amount,
            ':notes'  => $notes,
            ':status' => $status,
        ]);
    }

    public function deleteMonth(int $userId, int $year, int $month): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM expense_reports
             WHERE user_id = :uid AND report_year = :year AND report_month = :month"
        );
        return $stmt->execute([':uid' => $userId, ':year' => $year, ':month' => $month]);
    }

    /** Total amount declared for the whole year (used for a summary header). */
    public function getYearTotal(int $userId, int $year): float
    {
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM expense_reports WHERE user_id = :uid AND report_year = :year"
        );
        $stmt->execute([':uid' => $userId, ':year' => $year]);
        return (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
