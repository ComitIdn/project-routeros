<?php

namespace RouterOSFramework\Transports;

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\ResponseException;
use Throwable;

/**
 * Class ApiTransport
 * Communicates with RouterOS via binary socket API protocol (Default port 8728 / 8729 SSL).
 */
class ApiTransport implements TransportInterface
{
    private array $config;
    private ?Client $client = null;
    private ?string $lastError = null;

    public function __construct(array $config)
    {
        $this->config = [
            'host'    => $config['host'] ?? '192.168.88.1',
            'user'    => $config['user'] ?? ($config['username'] ?? 'admin'),
            'pass'    => $config['pass'] ?? ($config['password'] ?? ''),
            'port'    => (int)($config['port'] ?? 8728),
            'timeout' => (int)($config['timeout'] ?? 3),
            'ssl'     => (bool)($config['ssl'] ?? false),
        ];
    }

    public function connect(): bool
    {
        try {
            $this->client = new Client([
                'host'    => $this->config['host'],
                'user'    => $this->config['user'],
                'pass'    => $this->config['pass'],
                'port'    => $this->config['port'],
                'timeout' => $this->config['timeout'],
                'ssl'     => $this->config['ssl'],
            ]);
            $this->lastError = null;
            return true;
        } catch (Throwable $e) {
            $this->client = null;
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function disconnect(): void
    {
        $this->client = null;
    }

    public function isConnected(): bool
    {
        return $this->client !== null && $this->lastError === null;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function executeQuery(Query $query): array
    {
        if ($this->client === null) {
            if (!$this->connect()) {
                return ['error' => $this->lastError ?? 'Socket client not connected'];
            }
        }

        try {
            return $this->client->query($query)->read();
        } catch (ResponseException $e) {
            $this->lastError = $e->getMessage();
            return ['error' => $e->getMessage()];
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return ['error' => $e->getMessage()];
        }
    }

    public function read(string $endpoint, array $where = []): array
    {
        $query = new Query($endpoint);
        foreach ($where as $k => $v) {
            $query->where($k, $v);
        }
        return $this->executeQuery($query);
    }

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

    public function set(string $endpoint, string $id, array $params): array
    {
        $query = (new Query($endpoint))->equal('.id', $id);
        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $val = is_bool($v) ? ($v ? 'yes' : 'no') : (string)$v;
                $query->equal($k, $val);
            }
        }
        return $this->executeQuery($query);
    }

    public function remove(string $endpoint, string $id): array
    {
        $query = (new Query($endpoint))->equal('.id', $id);
        return $this->executeQuery($query);
    }

    public function execute(string $command, array $params = []): array
    {
        return $this->write($command, $params);
    }

    public function execRaw(string $cliCommand): string
    {
        // Execute terminal command via temporary script or command query
        $query = new Query($cliCommand);
        $res = $this->executeQuery($query);
        return json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getProtocol(): string
    {
        return 'api';
    }
}
