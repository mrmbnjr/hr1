<?php

/*
|--------------------------------------------------------------------------
| Global helpers
|--------------------------------------------------------------------------
*/

if (!function_exists('env')) {
    /**
     * Read an environment value. "true"/"false"/"null" become real values;
     * an unset or empty variable returns $default.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            default            => $value,
        };
    }
}

if (!function_exists('config')) {
    /**
     * config('app.debug') reads src/Config/app.php → ['debug'].
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $loaded = [];

        [$file, $item] = array_pad(explode('.', $key, 2), 2, null);

        if (!isset($loaded[$file])) {
            $path = SRC_PATH . '/Config/' . $file . '.php';
            $loaded[$file] = is_file($path) ? require $path : [];
        }

        return $item === null
            ? $loaded[$file]
            : ($loaded[$file][$item] ?? $default);
    }
}

if (!function_exists('base_url')) {
    /**
     * URL path prefix the app is served from.
     *   https://hr1.example.com/          → ''
     *   http://localhost/hr1/             → '/hr1'
     * Set APP_BASE_PATH in .env to force a value.
     */
    function base_url(): string
    {
        $override = env('APP_BASE_PATH');

        if ($override !== null) {
            $trimmed = trim((string) $override, '/');
            return $trimmed === '' ? '' : '/' . $trimmed;
        }

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $dir = rtrim(dirname($script), '/');

        return ($dir === '.' || $dir === '\\') ? '' : $dir;
    }
}

if (!function_exists('url')) {
    /**
     * url('?page=login') → "/?page=login" (or "/hr1/?page=login" in a subfolder)
     */
    function url(string $path = ''): string
    {
        return base_url() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * asset('css/style.css') → "/public/assets/css/style.css?v=<mtime>"
     * The mtime suffix makes browsers fetch the new file after a deploy.
     */
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $file = PUBLIC_PATH . '/assets/' . $path;
        $version = is_file($file) ? '?v=' . filemtime($file) : '';

        return url('public/assets/' . $path) . $version;
    }
}
