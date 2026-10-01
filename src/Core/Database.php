<?php

namespace Core;

use DateTime;
use DateTimeZone;
use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {

            $config = require __DIR__ . '/../Config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'] ?? 3306,
                $config['database'],
                $config['charset']
            );

            self::$pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password']
            );

            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            /*
             * Keep MySQL's clock (NOW(), CURRENT_TIMESTAMP) on the same
             * timezone as PHP. Shared hosts default MySQL to UTC, which
             * would otherwise put every timestamp 8 hours behind Manila.
             */
            $offset = (new DateTime('now', new DateTimeZone(date_default_timezone_get())))->format('P');
            self::$pdo->exec("SET time_zone = '" . $offset . "'");
        }

        return self::$pdo;
    }
}
