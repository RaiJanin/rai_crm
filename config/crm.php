<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deal Currency
    |--------------------------------------------------------------------------
    |
    | Deal values are stored as plain decimals. This ISO 4217 code is only
    | used to format those values in the UI (PHP = Philippine peso, ₱).
    |
    */

    'currency' => env('CRM_CURRENCY', 'PHP'),

    /*
    |--------------------------------------------------------------------------
    | Display Locale
    |--------------------------------------------------------------------------
    |
    | BCP 47 locale used to format money, numbers and dates in the UI.
    |
    */

    'locale' => env('CRM_LOCALE', 'en-PH'),

    /*
    |--------------------------------------------------------------------------
    | Display Timezone
    |--------------------------------------------------------------------------
    |
    | Timezone for dates and times rendered on the server, such as print
    | templates. Timestamps are still stored in the app timezone (UTC).
    |
    */

    'timezone' => env('CRM_TIMEZONE', env('APP_TIMEZONE', 'UTC')),

    /*
    |--------------------------------------------------------------------------
    | Attachment Upload Limit
    |--------------------------------------------------------------------------
    |
    | Maximum attachment size in kilobytes.
    |
    */

    'max_attachment_kb' => (int) env('CRM_MAX_ATTACHMENT_KB', 10240),

    /*
    |--------------------------------------------------------------------------
    | Support Ticket SLA Targets
    |--------------------------------------------------------------------------
    |
    | Calendar hours from ticket creation until the first reply and the
    | resolution are due, per priority. A ticket past either target without
    | meeting it is shown as breached.
    |
    */

    'ticket_sla' => [
        'urgent' => ['first_response' => 1, 'resolution' => 4],
        'high' => ['first_response' => 4, 'resolution' => 24],
        'medium' => ['first_response' => 8, 'resolution' => 72],
        'low' => ['first_response' => 24, 'resolution' => 120],
    ],

];
