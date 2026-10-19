<?php
declare(strict_types=1);

/**
 * PayrollManager
 * --------------------------------------------------------------------
 * Handles the "Payroll" tool: one payroll run per user/year/month,
 * generated from the active employees in `employees` and (optionally)
 * the CNSS/IR brackets configured in `tax_brackets` (the "Taxes" tool).
 *
 * ⚠️ IMPORTANT: calcCnss() and calcIr() below use SIMPLIFIED formulas
 * with a fallback default rate when no brackets are configured yet.
 * These are NOT guaranteed to match current Moroccan CNSS/IR law —
 * review and adjust the TODO-marked constants/logic against the
 * official rates before relying on this for real payroll.
 * --------------------------------------------------------------------
 */
class PayrollManager
{
    private PDO $pdo;

    // TODO: verify against current official CNSS rates (this is a simplified placeholder)
    private const CNSS_FALLBACK_RATE = 0.0448; // 4.48%
    private const CNSS_FALLBACK_CAP  = 6000.0; // monthly salary cap the rate applies to

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getYear(int $userId, int $year): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT run_month, lines_data, total_gross, total_net, status
             FROM payroll_runs WHERE user_id = :uid AND run_year = :year"
        );
        $stmt->execute([':uid' => $userId, ':year' => $year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $months = array_fill(1, 12, null);
        foreach ($rows as $row) {
            $row['lines_data'] = json_decode($row['lines_data'] ?? '[]', true) ?: [];
            $months[(int) $row['run_month']] = $row;
        }
        return $months;
    }

    public function getMonth(int $userId, int $year, int $month): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM payroll_runs WHERE user_id = :uid AND run_year = :year AND run_month = :month LIMIT 1"
        );
        $stmt->execute([':uid' => $userId, ':year' => $year, ':month' => $month]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['lines_data'] = json_decode($row['lines_data'] ?? '[]', true) ?: [];
        return $row;
    }

    /**
     * Builds (or rebuilds) a payroll run for one month from all active
     * employees, computing CNSS/IR with whatever brackets are configured.
     */
    public function generateMonth(int $userId, int $year, int $month): array
    {
        $employees = $this->getActiveEmployees($userId);
        $cnssBrackets = $this->getBrackets($userId, 'cnss');
        $irBrackets   = $this->getBrackets($userId, 'ir');

        $lines = [];
        foreach ($employees as $emp) {
            $gross = (float) $emp['salary'];
            $cnss  = $this->calcCnss($gross, $cnssBrackets);
            $ir    = $this->calcIr($gross - $cnss, $irBrackets);
            $net   = round($gross - $cnss - $ir, 2);

            $lines[] = [
                'employee_id'   => (int) $emp['id'],
                'employee_name' => $emp['full_name'],
                'gross'         => round($gross, 2),
                'cnss'          => round($cnss, 2),
                'ir'            => round($ir, 2),
                'net'           => $net,
            ];
        }

        $this->saveMonth($userId, $year, $month, $lines, 'draft');
        return $lines;
    }

    /**
     * Saves a month's payroll lines as-is (used both by generateMonth()
     * and if you let the user manually edit lines before finalizing).
     */
    public function saveMonth(int $userId, int $year, int $month, array $lines, string $status = 'draft'): bool
    {
        $totalGross = array_sum(array_column($lines, 'gross'));
        $totalNet   = array_sum(array_column($lines, 'net'));

        $sql = "INSERT INTO payroll_runs (user_id, run_year, run_month, lines_data, total_gross, total_net, status)
                VALUES (:uid, :year, :month, :lines, :gross, :net, :status)
                ON DUPLICATE KEY UPDATE
                    lines_data = VALUES(lines_data),
                    total_gross = VALUES(total_gross),
                    total_net = VALUES(total_net),
                    status = VALUES(status)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uid'    => $userId,
            ':year'   => $year,
            ':month'  => $month,
            ':lines'  => json_encode($lines, JSON_UNESCAPED_UNICODE),
            ':gross'  => $totalGross,
            ':net'    => $totalNet,
            ':status' => $status,
        ]);
    }

    private function getActiveEmployees(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, full_name, salary FROM employees WHERE user_id = :uid AND status = 'active' ORDER BY full_name ASC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getBrackets(int $userId, string $type): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT min_amount, max_amount, rate, deduction FROM tax_brackets
             WHERE user_id = :uid AND bracket_type = :type ORDER BY min_amount ASC"
        );
        $stmt->execute([':uid' => $userId, ':type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Falls back to a flat simplified rate if no CNSS brackets are configured yet. */
    private function calcCnss(float $gross, array $brackets): float
    {
        if (empty($brackets)) {
            $base = min($gross, self::CNSS_FALLBACK_CAP);
            return round($base * self::CNSS_FALLBACK_RATE, 2);
        }

        foreach ($brackets as $b) {
            $inRange = $gross >= (float) $b['min_amount']
                && ($b['max_amount'] === null || $gross <= (float) $b['max_amount']);
            if ($inRange) {
                return round($gross * ((float) $b['rate'] / 100), 2);
            }
        }
        return 0.0;
    }

    /** Progressive-style IR: applies the matching bracket's rate minus its deduction. Returns 0 if unconfigured. */
    private function calcIr(float $taxableMonthly, array $brackets): float
    {
        if (empty($brackets)) {
            return 0.0; // TODO: no IR brackets configured yet — see the "Taxes" tool
        }

        foreach ($brackets as $b) {
            $inRange = $taxableMonthly >= (float) $b['min_amount']
                && ($b['max_amount'] === null || $taxableMonthly <= (float) $b['max_amount']);
            if ($inRange) {
                $ir = ($taxableMonthly * ((float) $b['rate'] / 100)) - (float) $b['deduction'];
                return max(0.0, round($ir, 2));
            }
        }
        return 0.0;
    }
}
