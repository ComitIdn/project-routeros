<?php

namespace RouterOSFramework\Core;

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\ResponseException;
use RouterOSFramework\Transports\ApiTransport;
use RouterOSFramework\Transports\AutoTransport;
use RouterOSFramework\Transports\RestTransport;
use RouterOSFramework\Transports\SshTransport;
use RouterOSFramework\Transports\TelnetTransport;
use RouterOSFramework\Transports\TransportInterface;
use Throwable;

/**
 * Class Connection
 * Manages RouterOS communication across protocols (API socket, REST HTTP/HTTPS, SSH CLI, Telnet CLI).
 * Provides robust error recovery and dynamic transport switching.
 */
class Connection
{
    private ?TransportInterface $transport = null;
    private ?string $connectionError = null;
    private array $config;
    private string $protocol;

    /**
     * Connection constructor.
     *
     * @param array $config [
     *    'host'     => string,
     *    'user'     => string,
     *    'pass'     => string,
     *    'port'     => int,
     *    'timeout'  => int,
     *    'protocol' => string ('api'|'rest'|'ssh'|'telnet'|'auto'),
     *    'ssl'      => bool,
     * ]
     * @param bool $autoConnect
     */
    public function __construct(array $config = [], bool $autoConnect = true)
    {
        $this->protocol = strtolower((string)($config['protocol'] ?? 'api'));

        // Default ports according to protocol if not explicitly set
        $defaultPort = match ($this->protocol) {
            'rest'   => (!empty($config['ssl']) ? 443 : 80),
            'ssh'    => 22,
            'telnet' => 23,
            default  => 8728,
        };

        $this->config = [
            'host'       => $config['host'] ?? '192.168.88.1',
            'user'       => $config['user'] ?? ($config['username'] ?? 'admin'),
            'pass'       => $config['pass'] ?? ($config['password'] ?? ''),
            'port'       => (int)($config['port'] ?? $defaultPort),
            'timeout'    => (int)($config['timeout'] ?? 3),
            'protocol'   => $this->protocol,
            'ssl'        => (bool)($config['ssl'] ?? false),
            'verify_ssl' => (bool)($config['verify_ssl'] ?? false),
            'key'        => $config['key'] ?? null,
        ];

        $this->initTransport();

        if ($autoConnect) {
            $this->connect();
        }
    }

    /**
     * Initialize transport instance according to selected protocol.
     */
    private function initTransport(): void
    {
        $this->transport = match ($this->protocol) {
            'rest'   => new RestTransport($this->config),
            'ssh'    => new SshTransport($this->config),
            'telnet' => new TelnetTransport($this->config),
            'auto'   => new AutoTransport($this->config),
            default  => new ApiTransport($this->config),
        };
    }

    /**
     * Switch protocol dynamically at runtime.
     *
     * @param string $protocol 'api', 'rest', 'ssh', 'telnet', 'auto'
     * @param int|null $customPort Optional custom port
     * @return bool
     */
    public function setProtocol(string $protocol, ?int $customPort = null): bool
    {
        $this->protocol = strtolower($protocol);
        $this->config['protocol'] = $this->protocol;

        if ($customPort !== null) {
            $this->config['port'] = $customPort;
        } else {
            $this->config['port'] = match ($this->protocol) {
                'rest'   => (!empty($this->config['ssl']) ? 443 : 80),
                'ssh'    => 22,
                'telnet' => 23,
                default  => 8728,
            };
        }

        $this->initTransport();
        return $this->connect();
    }

    /**
     * Get active protocol name.
     */
    public function getProtocol(): string
    {
        return $this->transport ? $this->transport->getProtocol() : $this->protocol;
    }

    /**
     * Get underlying transport instance.
     */
    public function getTransport(): ?TransportInterface
    {
        return $this->transport;
    }

    /**
     * Connect or reconnect to RouterOS device.
     *
     * @return bool
     */
    public function connect(): bool
    {
        if ($this->transport === null) {
            $this->initTransport();
        }

        $connected = $this->transport->connect();
        if ($connected) {
            $this->connectionError = null;
            return true;
        }

        $this->connectionError = $this->transport->getLastError() ?? 'Failed to connect via ' . $this->protocol;
        return false;
    }

    /**
     * Check if connection is active.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->transport !== null && $this->transport->isConnected();
    }

    /**
     * Get connection error message if any.
     *
     * @return string|null
     */
    public function getConnectionError(): ?string
    {
        return $this->connectionError ?? ($this->transport ? $this->transport->getLastError() : null);
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
     * Get underlying evilfreelancer Client instance (if using ApiTransport).
     *
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        if ($this->transport instanceof ApiTransport) {
            return $this->transport->getClient();
        }
        return null;
    }

    /**
     * Execute a Query instance and return structured array response.
     *
     * @param Query $query
     * @return array
     */
    public function executeQuery(Query $query): array
    {
        if ($this->transport instanceof ApiTransport) {
            return $this->transport->executeQuery($query);
        }

        // Fallback for non-API transports
        $endpoint = $query->getEndpoint();
        return $this->read($endpoint);
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
        if ($this->transport === null) {
            $this->initTransport();
        }
        return $this->transport->read($endpoint, $where);
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
        if ($this->transport === null) {
            $this->initTransport();
        }
        return $this->transport->write($endpoint, $params);
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
        if ($this->transport === null) {
            $this->initTransport();
        }
        return $this->transport->set($endpoint, $idOrNumber, $params);
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
        if ($this->transport === null) {
            $this->initTransport();
        }
        return $this->transport->remove($endpoint, $idOrNumber);
    }

    /**
     * Execute command with parameters.
     *
     * @param string $command
     * @param array $params
     * @return array
     */
    public function execute(string $command, array $params = []): array
    {
        if ($this->transport === null) {
            $this->initTransport();
        }
        return $this->transport->execute($command, $params);
    }

    /**
     * Execute raw CLI terminal string.
     *
     * @param string $cliCommand
     * @return string
     */
    public function execRaw(string $cliCommand): string
    {
        if ($this->transport === null) {
            $this->initTransport();
        }
        return $this->transport->execRaw($cliCommand);
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
        return $this->write($endpoint, ['.id' => $idOrNumber]);
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
        return $this->write($endpoint, ['.id' => $idOrNumber]);
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
