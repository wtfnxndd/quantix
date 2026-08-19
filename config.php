<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('QUANTIX_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('QUANTIX_DB_PORT') ?: '3306',
        'name' => getenv('QUANTIX_DB_NAME') ?: 'quantix',
        'user' => getenv('QUANTIX_DB_USER') ?: 'root',
        'password' => getenv('QUANTIX_DB_PASSWORD') ?: '',
    ],
];
