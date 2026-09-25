<?php

return [
    'name' => $_ENV['APP_NAME'] ?? 'ApotekCare',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
    'timezone' => $_ENV['TIMEZONE'] ?? 'Asia/Jakarta',
    'currency' => 'IDR',
    'date_format' => 'd/m/Y',

    'session' => [
        'name' => $_ENV['SESSION_NAME'] ?? 'apotekcare_session',
        'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200),
        'secure' => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ],

    'upload_max_size' => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 2097152),
    'upload_allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
    'upload_allowed_ext' => ['jpg', 'jpeg', 'png', 'webp'],
];
