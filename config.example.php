<?php
/**
 * Project RouterOS Framework - Configuration Template
 * Copy this file to 'config.php' and adjust with your router credentials.
 */

return [
    // MikroTik RouterOS Credentials
    'mikrotik' => [
        'host'     => '192.168.88.1',
        'user'     => 'admin',
        'password' => '',
        'port'     => 8728, // 8728 (API) or 8729 (API-SSL)
        'timeout'  => 3,
    ],

    // Database Configuration (Zero-Config SQLite by default, or MySQL)
    'database' => [
        'driver' => 'sqlite',
        'file'   => __DIR__ . '/database/routeros.sqlite',

        // Uncomment for MySQL / MariaDB:
        // 'driver' => 'mysql',
        // 'host'   => '127.0.0.1',
        // 'port'   => 3306,
        // 'dbname' => 'routeros_db',
        // 'user'   => 'root',
        // 'pass'   => '',
    ],

    // Multi-Router Central Management (Optional)
    'routers' => [
        'hq' => [
            'name'     => 'Headquarters Router',
            'host'     => '192.168.88.1',
            'user'     => 'admin',
            'password' => '',
            'port'     => 8728,
        ],
    ],

    // Bot Gateway Settings (Telegram & WhatsApp)
    'bots' => [
        'telegram_token' => '',
        'whatsapp_api'   => '',
        'admin_chat_id'  => '',
    ],

    // REST API Backend Security & Performance Settings
    'api' => [
        'key'   => '',   // Optional API Bearer Key / X-API-KEY (leave empty to disable auth for local development)
        'gzip'  => true, // Enable gzip compression to reduce JSON payload transfer by ~85%
    ],
];
