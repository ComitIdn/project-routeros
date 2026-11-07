<?php

namespace RouterOSFramework\Core;

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\ResponseException;
use Throwable;

/**
 * Class Connection
 * Manages RouterOS API socket communication with robust error recovery.
 */
class Connection
{
    private ?Client $client = null;
    private ?string $connectionError = null;
    private array $config;

    /**
     * Connection constructor.
     *
     * @param array $config [
     *    'host'    => string,
     *    'user'    => string,
     *    'pass'    => string,
     *    'port'    => int,
     *    'timeout' => int,
     * ]
     * @param bool $autoConnect
     */
    public function __construct(array $config = [], bool $autoConnect = true)
    {
        $this->config = [
            'host'    => $config['host'] ?? '192.168.88.1',
            'user'    => $config['user'] ?? ($config['username'] ?? 'admin'),
            'pass'    => $config['pass'] ?? ($config['password'] ?? ''),
            'port'    => (int)($config['port'] ?? 8728),
            'timeout' => (int)($config['timeout'] ?? 3),
        ];

        if ($autoConnect) {
            $this->connect();
        }
    }

    /**
     * Connect or reconnect to RouterOS device.
     *
     * @return bool
     */
    public function connect(): bool
    {
        try {
            $this->client = new Client([
                'host'    => $this->config['host'],
                'user'    => $this->config['user'],
                'pass'    => $this->config['pass'],
                'port'    => $this->config['port'],
                'timeout' => $this->config['timeout'],
            ]);
            $this->connectionError = null;
            return true;
        } catch (Throwable $e) {
            $this->client = null;
            $this->connectionError = $e->getMessage();
            return false;
        }
    }

    /**
     * Check if connection is active.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->client !== null && $this->connectionError === null;
    }

    /**
     * Get connection error message if any.
     *
     * @return string|null
     */
    public function getConnectionError(): ?string
    {
        return $this->connectionError;
    }

    /**
     * Get configuration array.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get underlying evilfreelancer Client instance.
     *
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * Execute a Query instance and return structured array response.
     *
     * @param Query $query
     * @return array
     */
    public function executeQuery(Query $query): array
    {
        if ($this->client === null) {
            if (!$this->connect()) {
                return ['error' => $this->connectionError ?? 'RouterOS client not connected'];
            }
        }

        try {
            return $this->client->query($query)->read();
        } catch (ResponseException $e) {
            return ['error' => $e->getMessage()];
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Send print query with optional where filters.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/print'
     * @param array $where e.g. ['name' => 'admin', 'profile' => 'default']
     * @return array
     */
    public function read(string $endpoint, array $where = []): array
    {
        $query = new Query($endpoint);
        foreach ($where as $k => $v) {
            $query->where($k, $v);
        }
        return $this->executeQuery($query);
    }

    /**
     * Execute an add or action query.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/add'
     * @param array $params
     * @return array
     */
    public function write(string $endpoint, array $params = []): array
    {
        $query = new Query($endpoint);
        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $val = is_bool($v) ? ($v ? 'yes' : 'no') : (string)$v;
                $query->equal($k, $val);
            }
        }
        return $this->executeQuery($query);
    }

    /**
     * Update an item by .id or name.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/set'
     * @param string $idOrNumber
     * @param array $params
     * @return array
     */
    public function set(string $endpoint, string $idOrNumber, array $params): array
    {
        $query = (new Query($endpoint))->equal('.id', $idOrNumber);
        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $val = is_bool($v) ? ($v ? 'yes' : 'no') : (string)$v;
                $query->equal($k, $val);
            }
        }
        return $this->executeQuery($query);
    }

    /**
     * Remove item by ID.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/remove'
     * @param string $idOrNumber
     * @return array
     */
    public function remove(string $endpoint, string $idOrNumber): array
    {
        $query = (new Query($endpoint))->equal('.id', $idOrNumber);
        return $this->executeQuery($query);
    }

    /**
     * Enable an item.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/enable'
     * @param string $idOrNumber
     * @return array
     */
    public function enable(string $endpoint, string $idOrNumber): array
    {
        $query = (new Query($endpoint))->equal('.id', $idOrNumber);
        return $this->executeQuery($query);
    }

    /**
     * Disable an item.
     *
     * @param string $endpoint e.g. '/ip/hotspot/user/disable'
     * @param string $idOrNumber
     * @return array
     */
    public function disable(string $endpoint, string $idOrNumber): array
    {
        $query = (new Query($endpoint))->equal('.id', $idOrNumber);
        return $this->executeQuery($query);
    }

    /**
     * Find internal RouterOS .id by name in a print endpoint.
     *
     * @param string $endpoint
     * @param string $name
     * @return string|null
     */
    public function findIdByName(string $endpoint, string $name): ?string
    {
        $result = $this->read($endpoint, ['name' => $name]);
        if (!empty($result) && is_array($result) && !isset($result['error']) && isset($result[0]['.id'])) {
            return $result[0]['.id'];
        }
        return null;
    }
}
