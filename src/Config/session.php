<?php

/*
|--------------------------------------------------------------------------
| Session bootstrap
|--------------------------------------------------------------------------
| Hardened cookie settings for the HTTPS subdomain deployment. The cookie is
| only flagged "secure" when the request actually arrived over HTTPS, so
| local http:// development keeps working.
*/

if (session_status() === PHP_SESSION_NONE) {

    $https =
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('HR1SESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
