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

if (!function_exists('jobSalary')) {
    function jobSalary(array $job): string
    {
        /*
         * HR-entered salary range on the posting wins; the remainder
         * are the house defaults until every posting is filled in.
         */
        if (!empty($job['salary_range'])) {
            return (string) $job['salary_range'];
        }

        if (!empty($job['salary'])) {
            return (string) $job['salary'];
        }

        $title = strtolower((string) ($job['title'] ?? ''));
        if (strpos($title, 'manager') !== false || strpos($title, 'supervisor') !== false) {
            return '₱28,000 – ₱35,000 / month';
        }
        if (strpos($title, 'kitchen') !== false || strpos($title, 'cook') !== false || strpos($title, 'chef') !== false) {
            return '₱15,000 – ₱18,000 / month';
        }
        if (strpos($title, 'cashier') !== false) {
            return '₱14,000 – ₱16,500 / month';
        }

        return '₱13,500 – ₱16,000 / month';
    }
}

if (!function_exists('jobBenefits')) {
    /**
     * Parse the newline-separated benefits text stored on a job posting
     * into a clean list of individual benefits.
     *
     * Returns an empty array when HR has not entered (confirmed) any
     * benefits. Callers must render nothing in that case — unconfirmed
     * benefits must never be presented as actual company benefits.
     */
    function jobBenefits(array $job): array
    {
        $raw = (string) ($job['benefits'] ?? '');

        if (trim($raw) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);

        if (!is_array($lines)) {
            return [];
        }

        $benefits = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line !== '') {
                $benefits[] = $line;
            }
        }

        return $benefits;
    }
}
