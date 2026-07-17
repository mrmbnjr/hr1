<?php

    $config = require __DIR__ . '/env.php';

    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['dbname'],
        $db['charset']
    );

    $pdo = new PDO(
        $dsn,
        $db['username'],
        $db['password']
    );