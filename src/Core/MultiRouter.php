<?php

namespace RouterOSFramework\Core;

/**
 * Class MultiRouter
 * Enterprise multi-router manager supporting seamless switching between multiple MikroTik devices.
 */
class MultiRouter
{
    private array $routers = [];
    private array $instances = [];
    private ?string $defaultRouter = null;
    private ?Database $db = null;
    private ?JsonStorage $json = null;

    public function __construct(?Database $db = null, ?JsonStorage $json = null)
    {
        $this->db = $db;
        $this->json = $json ?? new JsonStorage();
        $this->loadFromStorage();
    }

    /**
     * Add router configuration.
     *
     * @param string $name Unique identifier (e.g. 'branch-1')
     * @param array $config RouterOS config [host, user, pass, port]
     * @param bool $isDefault
     * @return self
     */
    public function add(string $name, array $config, bool $isDefault = false): self
    {
        $this->routers[$name] = $config;
        if ($isDefault || $this->defaultRouter === null) {
            $this->defaultRouter = $name;
        }
        return $this;
    }

    /**
     * Switch to specific router and return its RouterOS instance.
     *
     * @param string|null $name
     * @return RouterOS
     */
    public function router(?string $name = null): RouterOS
    {
        $target = $name ?? $this->defaultRouter;
        if (!$target || !isset($this->routers[$target])) {
            throw new \InvalidArgumentException("Router '{$target}' not configured in MultiRouter.");
        }

        if (!isset($this->instances[$target])) {
            $config = $this->routers[$target];
            if ($this->db) {
                $config['database'] = [];
            }
            $this->instances[$target] = new RouterOS($config);
            if ($this->db) {
                $this->instances[$target]->withDatabase($this->db);
            }
        }

        return $this->instances[$target];
    }

    /**
     * Get list of all registered routers.
     *
     * @return array
     */
    public function getRouters(): array
    {
        return $this->routers;
    }

    /**
     * Check connection health for all routers.
     *
     * @return array
     */
    public function healthCheck(): array
    {
        $results = [];
        foreach ($this->routers as $name => $cfg) {
            try {
                $instance = $this->router($name);
                $connected = $instance->isConnected();
                $results[$name] = [
                    'host'      => $cfg['host'],
                    'port'      => $cfg['port'] ?? 8728,
                    'status'    => $connected ? 'online' : 'offline',
                    'error'     => $connected ? null : $instance->getConnectionError(),
                ];
            } catch (\Throwable $e) {
                $results[$name] = [
                    'host'      => $cfg['host'] ?? 'unknown',
                    'status'    => 'error',
                    'error'     => $e->getMessage(),
                ];
            }
        }
        return $results;
    }

    /**
     * Save router to storage (SQL or JSON).
     *
     * @param string $name
     * @param array $config
     * @return bool
     */
    public function saveRouter(string $name, array $config): bool
    {
        $this->add($name, $config);

        if ($this->db) {
            return (bool)$this->db->insert('routers', [
                'name'       => $name,
                'host'       => $config['host'],
                'port'       => (int)($config['port'] ?? 8728),
                'username'   => $config['user'] ?? ($config['username'] ?? 'admin'),
                'password'   => $config['pass'] ?? ($config['password'] ?? ''),
                'is_active'  => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $this->json->set('routers', $name, $config);
    }

    /**
     * Load registered routers from SQL or JSON storage.
     */
    private function loadFromStorage(): void
    {
        // 1. Try SQL Database
        if ($this->db) {
            $rows = $this->db->select('routers', 'is_active = 1');
            foreach ($rows as $r) {
                $this->add($r['name'], [
                    'host' => $r['host'],
                    'user' => $r['username'],
                    'pass' => $r['password'],
                    'port' => (int)$r['port'],
                ]);
            }
        }

        // 2. Try JSON Storage
        $jsonRouters = $this->json->all('routers');
        if (!empty($jsonRouters)) {
            foreach ($jsonRouters as $name => $cfg) {
                if (is_array($cfg) && isset($cfg['host'])) {
                    $this->add((string)$name, $cfg);
                }
            }
        }
    }
}
