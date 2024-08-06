<?php

require_once __DIR__ . '/bootstrap/app.php';

return [
    'paths' => [
        'migrations' => 'vendor/budgetcontrol/seeds/src/Resources/Migrations',
        'seeds' => 'resources/seeds'
    ],
    'environments' => [
        'default_migration_table' => 'test_migrations',
        'default_environment' => 'testing',
        'testing' => [
            'adapter'   => 'mysql',
            'host' => env('DB_HOST'),
            'name' => env('DB_DATABASE'),
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'charset'  => 'utf8',
            'port' => env('DB_PORT')
        ],
    ],
];