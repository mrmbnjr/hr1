<?php

defined('APP_STARTED') or exit('No direct script access allowed');

function requireRole(array $allowedRoles): void
{
    // If the role doesn't exist, deny access.
    if (!isset($_SESSION['role'])) {

        http_response_code(403);
        exit('Access denied.');

    }

    // If the user's role is not allowed, deny access.
    if (!in_array($_SESSION['role'], $allowedRoles, true)) {

        http_response_code(403);
        exit('You do not have permission to access this page.');

    }
}