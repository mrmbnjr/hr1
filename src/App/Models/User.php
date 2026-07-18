<?php

namespace App\Models;

use Core\Database;
use PDO;

class User
{
    public static function findByUsername(string $username)
    {
        $db = Database::connection();

        $sql = "SELECT *
                FROM users
                WHERE username = ?
                LIMIT 1";

        $stmt = $db->prepare($sql);
        $stmt->execute([$username]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}