<?php

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => isset($_SERVER['HTTPS']),
        'samesite' => 'Lax'
    ]);

    session_start();
}