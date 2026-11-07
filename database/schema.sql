-- =========================================================================
-- Project RouterOS Framework - SQL Schema (SQLite & MySQL Compatible)
-- =========================================================================

CREATE TABLE IF NOT EXISTS routers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    host VARCHAR(100) NOT NULL,
    port INTEGER DEFAULT 8728,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) DEFAULT '',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vouchers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    router_id INTEGER DEFAULT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    profile VARCHAR(100) DEFAULT 'default',
    limit_uptime VARCHAR(50) DEFAULT NULL,
    limit_bytes_total VARCHAR(50) DEFAULT NULL,
    price REAL DEFAULT 0,
    status VARCHAR(20) DEFAULT 'active',
    batch_tag VARCHAR(100) DEFAULT NULL,
    comment VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expired_at DATETIME DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS hotspot_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    router_id INTEGER DEFAULT NULL,
    routeros_id VARCHAR(50) DEFAULT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) DEFAULT NULL,
    profile VARCHAR(100) DEFAULT 'default',
    uptime_limit VARCHAR(50) DEFAULT NULL,
    bytes_limit VARCHAR(50) DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    mac_address VARCHAR(50) DEFAULT NULL,
    comment VARCHAR(255) DEFAULT NULL,
    disabled INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pppoe_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    router_id INTEGER DEFAULT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(100) NOT NULL,
    service VARCHAR(50) DEFAULT 'pppoe',
    profile VARCHAR(100) DEFAULT 'default',
    caller_id VARCHAR(50) DEFAULT NULL,
    local_address VARCHAR(50) DEFAULT NULL,
    remote_address VARCHAR(50) DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'active',
    due_date DATE DEFAULT NULL,
    comment VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    router_id INTEGER DEFAULT NULL,
    module VARCHAR(50) NOT NULL,
    action VARCHAR(50) NOT NULL,
    target VARCHAR(100) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'success',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
