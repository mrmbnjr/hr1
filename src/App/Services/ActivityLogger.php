<?php

namespace App\Services;

use Core\Database;
use Throwable;

class ActivityLogger
{
    public static function log(string $action, ?string $page = null): void
    {
        try {
            $userId = isset($_SESSION['user_id'])
                ? (int) $_SESSION['user_id']
                : null;
            $employeeId = isset($_SESSION['employee_id'])
                ? (int) $_SESSION['employee_id']
                : null;

            $db = Database::connection();

            if ($userId && !$employeeId) {
                $employeeStmt = $db->prepare(
                    'SELECT employee_id FROM users WHERE user_id = :user_id LIMIT 1'
                );
                $employeeStmt->execute([':user_id' => $userId]);
                $employeeId = (int) ($employeeStmt->fetchColumn() ?: 0);
            }

            $stmt = $db->prepare(
                'INSERT INTO activity_logs
                    (user_id, employee_id, action, page, request_method, ip_address, user_agent)
                 VALUES
                    (:user_id, :employee_id, :action, :page, :request_method, :ip_address, :user_agent)'
            );

            $stmt->execute([
                ':user_id' => $userId ?: null,
                ':employee_id' => $employeeId ?: null,
                ':action' => $action,
                ':page' => $page,
                ':request_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                ':ip_address' => self::clientIp(),
                ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (Throwable $exception) {
            error_log('Activity log failed: ' . $exception->getMessage());
        }
    }

    private static function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        return $ip !== null && filter_var($ip, FILTER_VALIDATE_IP)
            ? $ip
            : null;
    }
}
