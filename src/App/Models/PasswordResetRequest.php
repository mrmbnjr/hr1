<?php

namespace App\Models;

use Core\Database;
use PDO;

/**
 * "Contact HR" password reset requests submitted from the reset-password page.
 *
 * These are deliberately separate from `employee_requests`: the person filing
 * one may not be a signed-in employee, so there is no employee_id to link to.
 */
class PasswordResetRequest
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Every request, pending ones first, then newest first.
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                prr.request_id,
                prr.full_name,
                prr.identifier,
                prr.contact,
                prr.message,
                prr.status,
                prr.created_at,
                prr.resolved_at,
                prr.resolved_by,
                u.username AS resolved_by_username

            FROM password_reset_requests prr

            LEFT JOIN users u
                ON u.user_id = prr.resolved_by

            ORDER BY
                (prr.status = 'Pending') DESC,
                prr.created_at DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $requestId): ?array
    {
        $sql = "
            SELECT
                prr.request_id,
                prr.full_name,
                prr.identifier,
                prr.contact,
                prr.message,
                prr.status,
                prr.created_at,
                prr.resolved_at,
                prr.resolved_by,
                u.username AS resolved_by_username

            FROM password_reset_requests prr

            LEFT JOIN users u
                ON u.user_id = prr.resolved_by

            WHERE prr.request_id = :request_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);

        $stmt->execute();

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        return $request ?: null;
    }

    public function getPendingCount(): int
    {
        return (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM password_reset_requests
                WHERE status = 'Pending'
            ")
            ->fetchColumn();
    }

    /**
     * Action a pending reset request once. Settled requests cannot be reopened.
     */
    public function setStatus(
        int $requestId,
        string $status,
        ?int $handledBy
    ): bool
    {
        if (!in_array($status, ['Rejected', 'Completed'], true)) {
            return false;
        }

        $sql = "
            UPDATE password_reset_requests

            SET
                status = :status,
                resolved_at = NOW(),
                resolved_by = :resolved_by

            WHERE request_id = :request_id
                AND status = 'Pending'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':status'      => $status,
            ':resolved_by' => $handledBy,
            ':request_id'  => $requestId,
        ]) && $stmt->rowCount() === 1;
    }
}
