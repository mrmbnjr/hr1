<?php

namespace App\Models;

use Core\Database;
use PDO;

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public static function findByUsername(string $username)
    {
        $db = Database::connection();

        $sql = "
            SELECT
                u.*,
                r.role_code,
                r.role_name
            FROM users u
            INNER JOIN roles r
                ON r.role_id = u.role_id
            WHERE u.username = ?
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findById(int $userId): ?array
    {
        $db = Database::connection();

        $sql = "
            SELECT
                u.*,
                r.role_code,
                r.role_name
            FROM users u
            INNER JOIN roles r
                ON r.role_id = u.role_id
            WHERE u.user_id = ?
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
    public function getManagementData(): array
    {
        $sql = "
            SELECT
                u.user_id, u.employee_id, u.role_id, u.username, u.status,
                u.must_change_password, u.created_at, r.role_name,
                e.employee_number,
                CONCAT(COALESCE(ap.first_name, ''), ' ', COALESCE(ap.last_name, '')) AS fullname,
                ap.email, d.department_name
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            LEFT JOIN employees e ON e.employee_id = u.employee_id
            LEFT JOIN applicants ap ON ap.applicant_id = e.applicant_id
            LEFT JOIN positions p ON p.position_id = e.position_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            ORDER BY u.created_at DESC, fullname ASC
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRoles(): array
    {
        return $this->db->query(
            "SELECT role_id, role_code, role_name FROM roles ORDER BY role_name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments(): array
    {
        return $this->db->query(
            "SELECT department_id, department_name FROM departments ORDER BY department_name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAccount(int $userId, string $username, int $roleId, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET username = :username, role_id = :role_id, status = :status
             WHERE user_id = :user_id"
        );

        return $stmt->execute([
            ':username' => $username,
            ':role_id' => $roleId,
            ':status' => $status,
            ':user_id' => $userId,
        ]);
    }

    /**
     * Update the full sign-in identity and access of an account in one
     * statement: username, sign-in email, role and status.
     *
     * When the sign-in email actually changes we clear `email_verified_at`,
     * because a brand new address has not been verified yet.
     */
    public function updateAccountFull(
        int $userId,
        string $username,
        string $email,
        int $roleId,
        string $status,
        bool $resetVerification = false
    ): bool {
        $sql = 'UPDATE users
                SET username = :username,
                    email = :email,
                    role_id = :role_id,
                    status = :status';

        if ($resetVerification) {
            $sql .= ', email_verified_at = NULL';
        }

        $sql .= ' WHERE user_id = :user_id';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':role_id'  => $roleId,
            ':status'   => $status,
            ':user_id'  => $userId,
        ]);
    }

    public function setStatus(int $userId, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = :status WHERE user_id = :user_id"
        );

        return $stmt->execute([':status' => $status, ':user_id' => $userId]);
    }
}