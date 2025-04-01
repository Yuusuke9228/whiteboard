<?php
// config/config.php
return [
    'app' => [
        'name' => 'Whiteboard',
        'debug' => true,
        'url' => 'http://localhost',
        'timezone' => 'Asia/Tokyo',
        'locale' => 'ja',
    ],
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'database' => $_ENV['DB_DATABASE'] ?? 'whiteboards',
        'username' => $_ENV['DB_USERNAME'] ?? 'erphalcon',
        'password' => $_ENV['DB_PASSWORD'] ?? 'erphalcon',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],
    'websocket' => [
        'host' => '0.0.0.0',
        'port' => 8080,
    ],
    'session' => [
        'lifetime' => 120,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
    ],
];
