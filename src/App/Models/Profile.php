<?php

namespace App\Models;

use Core\Database;
use PDO;

/**
 * Read/write access for the signed-in user's own profile page.
 *
 * Design notes
 * ------------
 * - No schema is created or altered here. Every write targets columns that
 *   already exist on `users` and `applicants`.
 * - Reads are intentionally read-only and safe to call on every page load.
 */
class Profile
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * Everything the profile page needs about one account.
     *
     * Aliases avoid collisions between `users.email` (sign-in address) and
     * `applicants.email` (contact address).
     */
    public function getProfile(int $userId): ?array
    {
        $sql = "
            SELECT
                u.user_id,
                u.employee_id,
                u.role_id,
                u.username,
                u.email AS account_email,
                u.email_verified_at,
                u.status,
                u.must_change_password,
                u.created_at,
                r.role_code,
                r.role_name,
                e.employee_number,
                e.hire_date,
                e.employment_status,
                p.position_name,
                d.department_name,
                ap.applicant_id,
                ap.first_name,
                ap.middle_name,
                ap.last_name,
                ap.email AS personal_email,
                ap.phone,
                ap.address
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            LEFT JOIN employees e ON e.employee_id = u.employee_id
            LEFT JOIN applicants ap ON ap.applicant_id = e.applicant_id
            LEFT JOIN positions p ON p.position_id = e.position_id
            LEFT JOIN departments d ON d.department_id = p.department_id
            WHERE u.user_id = :user_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }

    /**
     * Most recent activity entries for the account.
     */
    public function getRecentActivity(int $userId, int $limit = 12): array
    {
        $limit = max(1, min(50, $limit));

        $sql = "
            SELECT activity_id, action, page, request_method, ip_address, created_at
            FROM activity_logs
            WHERE user_id = :user_id
            ORDER BY activity_id DESC
            LIMIT {$limit}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Small counters used by the activity panel and the side cards.
     */
    public function getActivitySummary(int $userId): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS last_week,
                COUNT(DISTINCT page) AS pages,
                MAX(created_at) AS last_at
            FROM activity_logs
            WHERE user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'last_week' => (int) ($row['last_week'] ?? 0),
            'pages'     => (int) ($row['pages'] ?? 0),
            'last_at'   => $row['last_at'] ?? null,
        ];
    }

    // -----------------------------------------------------------------
    // Uniqueness guards (used by the controller before writing)
    // -----------------------------------------------------------------

    public function usernameExists(string $username, int $userId): bool
    {
        return $this->valueTaken('users', 'username', $username, 'user_id', $userId);
    }

    public function accountEmailExists(string $email, int $userId): bool
    {
        return $this->valueTaken('users', 'email', $email, 'user_id', $userId);
    }

    public function personalEmailExists(string $email, int $applicantId): bool
    {
        return $this->valueTaken('applicants', 'email', $email, 'applicant_id', $applicantId);
    }

    public function phoneExists(string $phone, int $applicantId): bool
    {
        return $this->valueTaken('applicants', 'phone', $phone, 'applicant_id', $applicantId);
    }

    /**
     * True when $value already exists in $column, ignoring the row that
     * belongs to $ownerId so a record never collides with itself.
     *
     * The table/column names are internal literals, never user input.
     */
    private function valueTaken(
        string $table,
        string $column,
        string $value,
        string $ownerColumn,
        int $ownerId
    ): bool {
        $sql = "SELECT 1 FROM {$table} WHERE {$column} = :value AND {$ownerColumn} <> :owner_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':value' => $value, ':owner_id' => $ownerId]);

        return (bool) $stmt->fetchColumn();
    }

    // -----------------------------------------------------------------
    // Writes — existing columns only
    // -----------------------------------------------------------------

    /**
     * Update the sign-in identity of an account.
     *
     * When the email actually changes we clear `email_verified_at`, because a
     * brand new address has not been verified yet.
     */
    public function updateAccount(int $userId, string $username, string $email, bool $resetVerification): bool
    {
        $sql = 'UPDATE users SET username = :username, email = :email';

        if ($resetVerification) {
            $sql .= ', email_verified_at = NULL';
        }

        $sql .= ' WHERE user_id = :user_id';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':user_id'  => $userId,
        ]);
    }

    /**
     * Replace the password hash and clear the "must change password" flag.
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users
             SET password = :password, must_change_password = 0
             WHERE user_id = :user_id'
        );

        return $stmt->execute([
            ':password' => $passwordHash,
            ':user_id'  => $userId,
        ]);
    }

    /**
     * Update the personal details stored on the linked applicant record.
     */
    public function updatePersonalInfo(int $applicantId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE applicants
             SET first_name = :first_name,
                 middle_name = :middle_name,
                 last_name = :last_name,
                 email = :email,
                 phone = :phone,
                 address = :address
             WHERE applicant_id = :applicant_id'
        );

        return $stmt->execute([
            ':first_name'   => $data['first_name'],
            ':middle_name'  => $data['middle_name'],
            ':last_name'    => $data['last_name'],
            ':email'        => $data['email'],
            ':phone'        => $data['phone'],
            ':address'      => $data['address'],
            ':applicant_id' => $applicantId,
        ]);
    }
}
