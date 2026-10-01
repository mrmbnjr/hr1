<?php

/*
|--------------------------------------------------------------------------
| Application bootstrap
|--------------------------------------------------------------------------
| Loaded once by index.php (the front controller in the project root).
| Order matters: paths → composer → .env → helpers → PHP runtime → session.
*/

require_once __DIR__ . '/Config/path.php';

$autoload = BASE_PATH . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    exit('Server setup incomplete: the vendor/ folder is missing. '
        . 'Upload it (or run "composer install --no-dev") and reload.');
}

require_once $autoload;
require_once __DIR__ . '/Config/env.php';
require_once __DIR__ . '/Core/helpers.php';

/*
| PHP runtime
*/

date_default_timezone_set((string) config('app.timezone', 'Asia/Manila'));

$appDebug = (bool) config('app.debug', false);

error_reporting(E_ALL);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('display_startup_errors', $appDebug ? '1' : '0');
ini_set('log_errors', '1');

$logDir = STORAGE_PATH . '/logs';

if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php-error.log');
}

set_exception_handler(static function (Throwable $e) use ($appDebug): void {

    error_log(sprintf(
        'Uncaught %s: %s in %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
    }

    $wantsJson =
        str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

    $message = $appDebug
        ? $e->getMessage()
        : 'Something went wrong. Please try again later.';

    if ($wantsJson) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => false, 'message' => $message]);
        return;
    }

    echo '<!doctype html><meta charset="utf-8"><title>Server error</title>'
        . '<body style="font-family:sans-serif;padding:3rem;text-align:center">'
        . '<h1>500 — Server error</h1><p>'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        . '</p></body>';
});

/*
| Session
*/

require_once __DIR__ . '/Config/session.php';
