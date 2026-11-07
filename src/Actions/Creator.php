<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Creator
 * Dedicated operational class for creating and adding records across any RouterOS path.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Creator
{
    private Connection $conn;
    private Finder $finder;

    public function __construct(Connection $conn, ?Finder $finder = null)
    {
        $this->conn = $conn;
        $this->finder = $finder ?? new Finder($conn);
    }

    /**
     * Normalize endpoint path to always end with /add.
     */
    private function normalizeAddPath(string $path): string
    {
        $path = rtrim($path, '/');
        if (str_ends_with($path, '/print')) {
            $path = substr($path, 0, -6);
        }
        if (!str_ends_with($path, '/add')) {
            $path .= '/add';
        }
        return $path;
    }

    /**
     * Universal create method for any RouterOS path.
     *
     * @param string $path Target path e.g. '/ip/hotspot/user' or '/ip/address'
     * @param array $data Parameters to create
     * @return array
     */
    public function create(string $path, array $data): array
    {
        $addPath = $this->normalizeAddPath($path);
        return $this->conn->write($addPath, $data);
    }

    /**
     * Alias of create().
     */
    public function add(string $path, array $data): array
    {
        return $this->create($path, $data);
    }

    /**
     * Create record only if it does not already exist based on lookup filter.
     *
     * @param string $path Target path
     * @param array $lookup Where filter to check existence e.g. ['name' => 'client1']
     * @param array $data Data to create if not found
     * @return array
     */
    public function createIfNotExists(string $path, array $lookup, array $data): array
    {
        if ($this->finder->exists($path, $lookup)) {
            return ['status' => 'already_exists', 'lookup' => $lookup];
        }

        $merged = array_merge($lookup, $data);
        return $this->create($path, $merged);
    }

    /**
     * Create multiple records in batch.
     *
     * @param string $path Target path
     * @param array $items Array of record associative arrays
     * @return array
     */
    public function batchCreate(string $path, array $items): array
    {
        $results = [];
        $addPath = $this->normalizeAddPath($path);
        foreach ($items as $idx => $item) {
            $results[$idx] = $this->conn->write($addPath, $item);
        }
        return $results;
    }

    // --- QUICK SPECIALIZED BUILDERS ---

    public function hotspotUser(string $name, string $password, string $profile = 'default', array $extras = []): array
    {
        return $this->create('/ip/hotspot/user', array_merge([
            'name'     => $name,
            'password' => $password,
            'profile'  => $profile,
        ], $extras));
    }

    public function pppoeSecret(string $name, string $password, string $profile = 'default', array $extras = []): array
    {
        return $this->create('/ppp/secret', array_merge([
            'name'     => $name,
            'password' => $password,
            'profile'  => $profile,
            'service'  => 'pppoe',
        ], $extras));
    }

    public function ipAddress(string $address, string $interface, ?string $comment = null): array
    {
        $params = [
            'address'   => $address,
            'interface' => $interface,
        ];
        if ($comment !== null) {
            $params['comment'] = $comment;
        }
        return $this->create('/ip/address', $params);
    }

    public function queueSimple(string $name, string $target, string $maxLimit, array $extras = []): array
    {
        return $this->create('/queue/simple', array_merge([
            'name'      => $name,
            'target'    => $target,
            'max-limit' => $maxLimit,
        ], $extras));
    }

    public function firewallRule(string $chain, array $properties): array
    {
        return $this->create('/ip/firewall/filter', array_merge(['chain' => $chain], $properties));
    }

    public function natRule(string $chain, array $properties): array
    {
        return $this->create('/ip/firewall/nat', array_merge(['chain' => $chain], $properties));
    }

    public function ptmpAccessList(string $macAddress, ?string $interface = null, ?string $signalRange = null, ?string $comment = null, bool $allow = true): array
    {
        $params = [
            'mac-address'    => strtoupper(str_replace(['-', '.'], ':', $macAddress)),
            'authentication' => $allow ? 'yes' : 'no',
            'forwarding'     => $allow ? 'yes' : 'no',
        ];
        if ($interface) {
            $params['interface'] = $interface;
        }
        if ($signalRange) {
            $params['signal-range'] = $signalRange;
        }
        if ($comment) {
            $params['comment'] = $comment;
        }
        return $this->create('/interface/wireless/access-list', $params);
    }

    public function vlan(string $name, int $vlanId, string $interface): array
    {
        return $this->create('/interface/vlan', [
            'name'      => $name,
            'vlan-id'   => (string)$vlanId,
            'interface' => $interface,
        ]);
    }

    public function dnsStatic(string $name, string $address, int $ttl = 300): array
    {
        return $this->create('/ip/dns/static', [
            'name'    => $name,
            'address' => $address,
            'ttl'     => "{$ttl}s",
        ]);
    }
}
