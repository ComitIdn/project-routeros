<?php

namespace RouterOSFramework\Transports;

/**
 * Class AutoTransport
 * Automatically detects and selects the best working transport protocol
 * from available RouterOS services: API (8728) -> REST (80/443) -> SSH (22) -> Telnet (23).
 */
class AutoTransport implements TransportInterface
{
    private array $config;
    private ?TransportInterface $activeTransport = null;
    private ?string $lastError = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function connect(): bool
    {
        // Try protocols in sequence
        $candidates = [
            'api'    => fn() => new ApiTransport($this->config),
            'rest'   => fn() => new RestTransport($this->config),
            'ssh'    => fn() => new SshTransport($this->config),
            'telnet' => fn() => new TelnetTransport($this->config),
        ];

        foreach ($candidates as $name => $factory) {
            /** @var TransportInterface $transport */
            $transport = $factory();
            if ($transport->connect()) {
                $this->activeTransport = $transport;
                $this->lastError = null;
                return true;
            }
        }

        $this->lastError = "AutoTransport failed: unable to connect via API, REST, SSH, or Telnet";
        return false;
    }

    public function disconnect(): void
    {
        if ($this->activeTransport) {
            $this->activeTransport->disconnect();
            $this->activeTransport = null;
        }
    }

    public function isConnected(): bool
    {
        return $this->activeTransport !== null && $this->activeTransport->isConnected();
    }

    public function getActiveTransport(): ?TransportInterface
    {
        return $this->activeTransport;
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?? ($this->activeTransport ? $this->activeTransport->getLastError() : null);
    }

    public function read(string $endpoint, array $where = []): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['error' => $this->getLastError()];
        }
        return $this->activeTransport->read($endpoint, $where);
    }

    public function write(string $endpoint, array $params = []): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['error' => $this->getLastError()];
        }
        return $this->activeTransport->write($endpoint, $params);
    }

    public function set(string $endpoint, string $id, array $params): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['error' => $this->getLastError()];
        }
        return $this->activeTransport->set($endpoint, $id, $params);
    }

    public function remove(string $endpoint, string $id): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['error' => $this->getLastError()];
        }
        return $this->activeTransport->remove($endpoint, $id);
    }

    public function execute(string $command, array $params = []): array
    {
        if (!$this->isConnected() && !$this->connect()) {
            return ['error' => $this->getLastError()];
        }
        return $this->activeTransport->execute($command, $params);
    }

    public function execRaw(string $cliCommand): string
    {
        if (!$this->isConnected() && !$this->connect()) {
            return '';
        }
        return $this->activeTransport->execRaw($cliCommand);
    }

    public function getProtocol(): string
    {
        return $this->activeTransport ? $this->activeTransport->getProtocol() : 'auto';
    }
}
