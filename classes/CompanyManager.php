<?php
declare(strict_types=1);

/**
 * CompanyManager
 * --------------------------------------------------------------------
 * Handles the "Company" tool: one company profile per user.
 * --------------------------------------------------------------------
 */
class CompanyManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByUser(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE user_id = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Creates or updates the company profile for a user (upsert).
     * $data keys expected: company_name, legal_name, description,
     * vat_number, registration_number, cie_number, bank_name, bank_account_number
     */
    public function save(int $userId, array $data): bool
    {
        $sql = "INSERT INTO companies
                    (user_id, company_name, legal_name, description, vat_number,
                     registration_number, cie_number, bank_name, bank_account_number)
                VALUES
                    (:user_id, :company_name, :legal_name, :description, :vat_number,
                     :registration_number, :cie_number, :bank_name, :bank_account_number)
                ON DUPLICATE KEY UPDATE
                    company_name = VALUES(company_name),
                    legal_name = VALUES(legal_name),
                    description = VALUES(description),
                    vat_number = VALUES(vat_number),
                    registration_number = VALUES(registration_number),
                    cie_number = VALUES(cie_number),
                    bank_name = VALUES(bank_name),
                    bank_account_number = VALUES(bank_account_number)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':user_id'              => $userId,
            ':company_name'         => $data['company_name'],
            ':legal_name'           => $data['legal_name'] ?: null,
            ':description'          => $data['description'] ?: null,
            ':vat_number'           => $data['vat_number'] ?: null,
            ':registration_number'  => $data['registration_number'] ?: null,
            ':cie_number'           => $data['cie_number'] ?: null,
            ':bank_name'            => $data['bank_name'] ?: null,
            ':bank_account_number'  => $data['bank_account_number'] ?: null,
        ]);
    }
}
