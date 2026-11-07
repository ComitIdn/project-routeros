-- =========================================================================
-- Project RouterOS Framework - SQL Schema (SQLite & MySQL Compatible)
-- Optimized for high-throughput (10,000+ vouchers, indexed lookups, zero full-table scans)
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
    used_by VARCHAR(100) DEFAULT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expired_at DATETIME DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_vouchers_username ON vouchers(username);
CREATE INDEX IF NOT EXISTS idx_vouchers_batch ON vouchers(batch_tag);
CREATE INDEX IF NOT EXISTS idx_vouchers_status ON vouchers(status);
CREATE INDEX IF NOT EXISTS idx_vouchers_router ON vouchers(router_id);

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

CREATE INDEX IF NOT EXISTS idx_hotspot_users_name ON hotspot_users(username);
CREATE INDEX IF NOT EXISTS idx_hotspot_users_profile ON hotspot_users(profile);
CREATE INDEX IF NOT EXISTS idx_hotspot_users_router ON hotspot_users(router_id);

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

CREATE INDEX IF NOT EXISTS idx_pppoe_users_name ON pppoe_users(username);
CREATE INDEX IF NOT EXISTS idx_pppoe_users_due ON pppoe_users(due_date);
CREATE INDEX IF NOT EXISTS idx_pppoe_users_status ON pppoe_users(status);

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

CREATE INDEX IF NOT EXISTS idx_logs_module ON activity_logs(module);
CREATE INDEX IF NOT EXISTS idx_logs_created ON activity_logs(created_at);
