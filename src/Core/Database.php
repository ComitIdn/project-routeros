<?php

namespace RouterOSFramework\Core;

use PDO;
use PDOException;

/**
 * Class Database
 * Ultra-lightweight SQL database wrapper supporting SQLite and MySQL via PDO.
 */
class Database
{
    private PDO $pdo;
    private string $driver = 'sqlite';

    /**
     * Database constructor.
     *
     * @param array $config [
     *    'driver'   => 'sqlite'|'mysql',
     *    'file'     => '/path/to/database.sqlite', // For SQLite
     *    'host'     => '127.0.0.1',                // For MySQL
     *    'port'     => 3306,
     *    'database' => 'routeros',
     *    'username' => 'root',
     *    'password' => ''
     * ]
     */
    public function __construct(array $config = [])
    {
        $this->driver = strtolower($config['driver'] ?? 'sqlite');

        if ($this->driver === 'mysql') {
            $host = $config['host'] ?? '127.0.0.1';
            $port = (int)($config['port'] ?? 3306);
            $dbName = $config['database'] ?? ($config['dbname'] ?? 'routeros');
            $user = $config['username'] ?? ($config['user'] ?? 'root');
            $pass = $config['password'] ?? ($config['pass'] ?? '');
            $persistent = (bool)($config['persistent'] ?? false);

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            if ($persistent) {
                $options[PDO::ATTR_PERSISTENT] = true;
            }
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } else {
            // Default: SQLite
            $this->driver = 'sqlite';
            $dbFile = $config['file'] ?? (__DIR__ . '/../../database/routeros.sqlite');
            $dir = dirname($dbFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $dsn = "sqlite:{$dbFile}";
            $this->pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // High-concurrency performance pragmas for SQLite
            try {
                $this->pdo->exec("PRAGMA journal_mode = WAL; PRAGMA synchronous = NORMAL; PRAGMA temp_store = MEMORY; PRAGMA cache_size = -64000;");
            } catch (\Throwable $e) {
                // Non-critical if pragma fails
            }
        }

        $this->ensureTables();
    }

    /**
     * Ensure database schema tables and indexes exist.
     */
    private function ensureTables(): void
    {
        $schemaFile = __DIR__ . '/../../database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            if ($sql) {
                if ($this->driver === 'mysql') {
                    // Convert SQLite syntax to MySQL syntax
                    $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
                    $sql = str_replace('REAL DEFAULT', 'DECIMAL(12,2) DEFAULT', $sql);
                }

                // Execute statements individually to ensure indexes & tables are created cleanly
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $stmt) {
                    if (!empty($stmt)) {
                        try {
                            $this->pdo->exec($stmt);
                        } catch (\Throwable $e) {
                            // Suppress already exists or index warnings
                        }
                    }
                }
            }
        }

        // Auto-migrate new columns for backwards compatibility
        $migrations = [
            'pppoe_users' => [
                'caller_id' => 'VARCHAR(50) DEFAULT NULL',
                'status'    => "VARCHAR(20) DEFAULT 'active'",
                'due_date'  => 'DATE DEFAULT NULL',
            ],
            'vouchers' => [
                'used_by'   => 'VARCHAR(100) DEFAULT NULL',
                'used_at'   => 'DATETIME DEFAULT NULL',
            ],
        ];

        foreach ($migrations as $table => $cols) {
            foreach ($cols as $col => $type) {
                try {
                    $this->pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$type}");
                } catch (\Throwable $e) {
                    // Ignored if column already exists
                }
            }
        }
    }

    /**
     * Get underlying PDO instance.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get active database driver name ('sqlite' or 'mysql').
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Begin database transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->inTransaction() ? true : $this->pdo->beginTransaction();
    }

    /**
     * Commit database transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->inTransaction() ? $this->pdo->commit() : true;
    }

    /**
     * Rollback database transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->inTransaction() ? $this->pdo->rollBack() : true;
    }

    /**
     * Check if currently in transaction.
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Execute callback inside database transaction.
     * Automatically commits on success and rolls back on exception.
     *
     * @param callable $callback
     * @return mixed
     * @throws \Throwable
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * High-speed batch insert using multi-row VALUES in chunks inside a transaction.
     * Capable of inserting 10,000+ rows into MySQL / SQLite in milliseconds.
     *
     * @param string $table
     * @param array $rows Array of associative arrays (each row sharing same columns)
     * @param int $chunkSize Number of rows per single INSERT statement (default 500)
     * @return int Total rows successfully inserted
     */
    public function insertBatch(string $table, array $rows, int $chunkSize = 500): int
    {
        if (empty($rows)) {
            return 0;
        }

        $firstRow = reset($rows);
        $columns = array_keys($firstRow);
        $columnList = implode(', ', $columns);
        $totalInserted = 0;

        $wasInTx = $this->inTransaction();
        if (!$wasInTx) {
            $this->beginTransaction();
        }

        try {
            $chunks = array_chunk($rows, max(1, $chunkSize));
            foreach ($chunks as $chunk) {
                $placeholders = [];
                $flatParams = [];

                foreach ($chunk as $row) {
                    $rowPlaceholders = [];
                    foreach ($columns as $col) {
                        $rowPlaceholders[] = '?';
                        $flatParams[] = $row[$col] ?? null;
                    }
                    $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
                }

                $sql = "INSERT INTO {$table} ({$columnList}) VALUES " . implode(', ', $placeholders);
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($flatParams);
                $totalInserted += count($chunk);
            }

            if (!$wasInTx) {
                $this->commit();
            }
        } catch (\Throwable $e) {
            if (!$wasInTx) {
                $this->rollBack();
            }
            throw $e;
        }

        return $totalInserted;
    }

    /**
     * Execute SELECT query and return array of records.
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Alias of query() to fetch all rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params);
    }

    /**
     * Execute an INSERT/UPDATE/DELETE statement.
     *
     * @param string $sql
     * @param array $params
     * @return bool
     */
    public function execute(string $sql, array $params = []): bool
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Insert a row into a table and return the last insert ID.
     *
     * @param string $table
     * @param array $data Associative array column => value
     * @return int|string|false
     */
    public function insert(string $table, array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update rows in a table.
     *
     * @param string $table
     * @param array $data Associative array column => value
     * @param string $where Where clause (e.g. "id = :id")
     * @param array $whereParams Associative array of parameters for where clause
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $params = [];
        foreach ($data as $col => $val) {
            $paramName = "set_{$col}";
            $setParts[] = "{$col} = :{$paramName}";
            $params[$paramName] = $val;
        }
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $mergedParams = array_merge($params, $whereParams);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($mergedParams);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Delete rows from a table.
     *
     * @param string $table
     * @param string $where Where clause (e.g. "id = :id")
     * @param array $params
     * @return int Number of deleted rows
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Find single record matching condition.
     *
     * @param string $table
     * @param string $where
     * @param array $params
     * @return array|null
     */
    public function find(string $table, string $where, array $params = []): ?array
    {
        $sql = "SELECT * FROM {$table} WHERE {$where} LIMIT 1";
        $results = $this->query($sql, $params);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Select records from table with optional where, order, limit.
     *
     * @param string $table
     * @param string $where
     * @param array $params
     * @param string $orderBy
     * @param int $limit
     * @return array
     */
    public function select(
        string $table,
        string $where = '',
        array $params = [],
        string $orderBy = '',
        int $limit = 0
    ): array {
        $sql = "SELECT * FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->query($sql, $params);
    }

    /**
     * Count rows matching condition.
     *
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int
     */
    public function count(string $table, string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        $res = $this->query($sql, $params);
        return isset($res[0]['total']) ? (int)$res[0]['total'] : 0;
    }

    /**
     * Log system activity to database.
     *
     * @param string $module
     * @param string $action
     * @param string|null $target
     * @param string|null $details
     * @param string $status
     * @param int|null $routerId
     */
    public function log(
        string $module,
        string $action,
        ?string $target = null,
        ?string $details = null,
        string $status = 'success',
        ?int $routerId = null
    ): void {
        $this->insert('activity_logs', [
            'router_id'  => $routerId,
            'module'     => $module,
            'action'     => $action,
            'target'     => $target,
            'details'    => $details,
            'status'     => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get logs filtered by specific router ID.
     *
     * @param int|string $routerId
     * @param int $limit
     * @return array
     */
    public function getRouterLogs($routerId, int $limit = 50): array
    {
        return $this->select('activity_logs', 'router_id = :r', ['r' => $routerId], 'id DESC', $limit);
    }

    /**
     * Get database operational metrics and row counts.
     */
    public function stats(): array
    {
        return [
            'driver'        => $this->driver,
            'vouchers'      => $this->count('vouchers'),
            'hotspot_users' => $this->count('hotspot_users'),
            'pppoe_users'   => $this->count('pppoe_users'),
            'routers'       => $this->count('routers'),
            'activity_logs' => $this->count('activity_logs'),
        ];
    }
}
