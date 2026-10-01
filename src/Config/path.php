<?php

/*
|--------------------------------------------------------------------------
| Filesystem paths
|--------------------------------------------------------------------------
| BASE_PATH is the project root — the folder that holds index.php, src/,
| resources/, storage/ and vendor/. index.php defines it first; this is only
| a fallback for CLI scripts that load the bootstrap directly.
*/

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

define('SRC_PATH', BASE_PATH . '/src');
define('VIEW_PATH', BASE_PATH . '/resources/views');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');
