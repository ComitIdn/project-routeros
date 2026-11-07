<?php

namespace RouterOSFramework\Modules;

use RouterOS\Query;
use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class System
 * System resources, identity, clock, scripts, scheduler, reboot, ping, and DNS module.
 */
class System
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    public function getResources(): array
    {
        return $this->conn->read('/system/resource/print');
    }

    public function getIdentity(): array
    {
        return $this->conn->read('/system/identity/print');
    }

    public function setIdentity(string $name): array
    {
        return $this->conn->write('/system/identity/set', ['name' => $name]);
    }

    public function getClock(): array
    {
        return $this->conn->read('/system/clock/print');
    }

    public function reboot(): array
    {
        if ($this->db) {
            $this->db->log('System', 'Reboot', null, 'Router reboot initiated', 'success');
        }
        return $this->conn->write('/system/reboot');
    }

    // --- SCRIPTS ---

    public function getScripts(): array
    {
        return $this->conn->read('/system/script/print');
    }

    public function addScript(string $name, string $source): array
    {
        return $this->conn->write('/system/script/add', [
            'name'   => $name,
            'source' => $source,
        ]);
    }

    public function removeScript(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/system/script/print', $name) ?? $name);
        return $this->conn->remove('/system/script/remove', $id);
    }

    // --- SCHEDULER ---

    public function getSchedulers(): array
    {
        return $this->conn->read('/system/scheduler/print');
    }

    public function addScheduler(
        string $name,
        string $onEvent,
        string $interval,
        string $startDate = '',
        string $startTime = ''
    ): array {
        $params = [
            'name'     => $name,
            'on-event' => $onEvent,
            'interval' => $interval,
        ];
        if (!empty($startDate)) $params['start-date'] = $startDate;
        if (!empty($startTime)) $params['start-time'] = $startTime;

        return $this->conn->write('/system/scheduler/add', $params);
    }

    public function removeScheduler(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/system/scheduler/print', $name) ?? $name);
        return $this->conn->remove('/system/scheduler/remove', $id);
    }

    // --- LOGS & DIAGNOSTICS ---

    public function getLogs(): array
    {
        return $this->conn->read('/log/print');
    }

    public function ping(string $address, int $count = 4): array
    {
        $query = (new Query('/ping'))
            ->equal('address', $address)
            ->equal('count', (string)$count);
        return $this->conn->executeQuery($query);
    }

    public function getDns(): array
    {
        return $this->conn->read('/ip/dns/print');
    }

    public function getDnsCache(): array
    {
        return $this->conn->read('/ip/dns/cache/print');
    }

    public function getNetwatch(): array
    {
        return $this->conn->read('/tool/netwatch/print');
    }

    public function getCpuProfile(): array
    {
        return $this->conn->read('/tool/profile/print');
    }

    /**
     * Get RouterOS version string (e.g. '7.15.2' or '6.49.8').
     */
    public function getVersion(): string
    {
        $res = $this->getResources();
        return $res[0]['version'] ?? 'unknown';
    }

    /**
     * Get RouterOS major version number (e.g. 6 or 7).
     */
    public function getMajorVersion(): int
    {
        $v = $this->getVersion();
        if (preg_match('/^([0-9]+)/', $v, $matches)) {
            return (int)$matches[1];
        }
        return 7;
    }

    /**
     * Check if router is running RouterOS v7.xx or higher.
     */
    public function isV7(): bool
    {
        return $this->getMajorVersion() >= 7;
    }

    /**
     * Check if router is running RouterOS v6.xx.
     */
    public function isV6(): bool
    {
        return $this->getMajorVersion() === 6;
    }
}
