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

    /*
    |--------------------------------------------------------------------------
    | Application retention
    |--------------------------------------------------------------------------
    | Enforced by App\Services\ApplicationRetentionService whenever a job
    | posting is closed, and opportunistically on the Applicants / Recruitment
    | pages.
    |
    |   - A closed posting's rejected applications are removed immediately.
    |   - Its remaining (non-hired) applications are removed once the posting
    |     has been closed for `closed_application_retention_days` days.
    |
    | Hired applications are never removed — they back the employee record.
    */

    'closed_application_retention_days' => max(
        1,
        (int) env('CLOSED_APPLICATION_RETENTION_DAYS', 30)
    ),

    // Minimum seconds between opportunistic runs on page loads. Set to 0 to
    // run on every load.
    'application_cleanup_interval' => max(
        0,
        (int) env('APPLICATION_CLEANUP_INTERVAL', 3600)
    ),
];
