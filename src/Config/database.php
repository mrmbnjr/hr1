<?php

/*
| Values come from .env. On Hostinger the host is normally "localhost" and
| the database name / user are prefixed (e.g. u123456789_hr1).
*/

return [
    'host'     => env('DB_HOST', 'localhost'),
    'port'     => (int) env('DB_PORT', 3306),
    'database' => env('DB_NAME', 'hr1_db'),
    'username' => env('DB_USER', 'root'),
    'password' => (string) env('DB_PASSWORD', ''),
    'charset'  => 'utf8mb4',
];
