<?php

return [
    'name'     => env('APP_NAME', 'RAM-YUM HR1'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),

    // Optional. Only used where a full absolute URL is needed.
    'url'      => rtrim((string) env('APP_URL', ''), '/'),

    // Philippine deployment; override with APP_TIMEZONE in .env.
    'timezone' => env('APP_TIMEZONE', 'Asia/Manila'),

    'rejected_applicant_retention_days' => max(
        1,
        (int) env('REJECTED_APPLICANT_RETENTION_DAYS', 30)
    ),
];
