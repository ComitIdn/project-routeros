<?php

return [
    // MikroTik RouterOS API Credentials
    'mikrotik' => [
        'host'     => '192.168.101.7',
        'user'     => 'admin',
        'password' => '',
        'port'     => 8728, // Port API default MikroTik
        'timeout'  => 3,
    ],

    // Lightweight SQL Database (SQLite zero-config atau MySQL)
    'database' => [
        'driver'   => 'sqlite', // 'sqlite' atau 'mysql'
        'file'     => __DIR__ . '/database/routeros.sqlite', // Path file SQLite

        // Pengaturan jika menggunakan MySQL / MariaDB (uncomment jika perlu):
        // 'host'     => '127.0.0.1',
        // 'port'     => 3306,
        // 'database' => 'routeros',
        // 'username' => 'root',
        // 'password' => '',
    ],

    // REST API Backend Security & Performance Settings
    'api' => [
        'key'   => '',   // Optional API Bearer Key / X-API-KEY (empty = allow local requests)
        'gzip'  => true, // Enable gzip compression for lightning-fast JSON delivery
    ],
];
