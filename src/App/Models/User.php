<?php

namespace App\Models;

use Core\Database;
use PDO;

class User
{
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
}