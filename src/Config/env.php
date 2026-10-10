<?php

/*
|--------------------------------------------------------------------------
| .env loader
|--------------------------------------------------------------------------
| Reads BASE_PATH/.env into getenv(), $_ENV and $_SERVER.
| - "KEY=value", quoted values ("..." or '...') and "export KEY=value"
| - full-line comments (#) and trailing " # comment" on unquoted values
| - Windows (CRLF) line endings
| Quote any value that contains spaces or a # character.
*/

(static function (string $file): void {

    if (!is_file($file) || !is_readable($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = ltrim(substr($line, 7));
        }

        $pos = strpos($line, '=');

        if ($pos === false) {
            continue;
        }

        $name = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($name === '') {
            continue;
        }

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {

            $quote = $value[0];
            $end = strrpos($value, $quote);
            $value = ($end !== false && $end > 0)
                ? substr($value, 1, $end - 1)
                : substr($value, 1);

            if ($quote === '"') {
                $value = str_replace(['\\"', '\\n'], ['"', "\n"], $value);
            }

        } else {
            $value = trim((string) preg_replace('/\s+#.*$/', '', $value));
        }

        // Keep environment variables supplied by Docker, Compose, or the host
        // ahead of values from the local .env file.
        if (getenv($name) !== false) {
            continue;
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

})(BASE_PATH . '/.env');
