<?php

    return [
        'db' => [
            'host'     => getenv('DB_HOST') ?: '127.0.0.1',
            'port'     => getenv('DB_PORT') ?: 3306,
            'dbname'   => getenv('DB_NAME'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset'  => 'utf8mb4',
        ]
    ];