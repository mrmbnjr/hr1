<?php

function requireRole(array $roles)
{
    if (!isset($_SESSION['role'])) {

        header("Location: /client/index.php");
        exit();

    }

    if (!in_array($_SESSION['role'], $roles)) {

        http_response_code(403);

        die("Access Denied");

    }
}