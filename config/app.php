<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Tes Fisik KONI Sumbar'),
    'url' => rtrim((string) env('APP_URL', 'http://localhost/tesfisik'), '/'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'timezone' => env('APP_TIMEZONE', 'Asia/Jakarta'),
    'google_sheet_id' => env('GOOGLE_SHEET_ID', ''),
    'google_sheet_gid' => env('GOOGLE_SHEET_GID', ''),
    'google_sheet_name' => env('GOOGLE_SHEET_NAME', 'Atlit dan Pelatih'),
];
