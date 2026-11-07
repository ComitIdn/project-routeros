<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class Ip
 * Full MikroTik IP configuration module (Addresses, Pools, DHCP Server, DHCP Leases, ARP, Static DNS).
 */
class Ip
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    // --- IP ADDRESSES ---

    public function getAddresses(): array
    {
        return $this->conn->read('/ip/address/print');
    }

    public function addAddress(string $address, string $interface, string $comment = ''): array
    {
        $params = [
            'address'   => $address,
            'interface' => $interface,
        ];
        if (!empty($comment)) $params['comment'] = $comment;
        return $this->conn->write('/ip/address/add', $params);
    }

    public function removeAddress(string $id): array
    {
        return $this->conn->remove('/ip/address/remove', $id);
    }

    // --- IP POOLS ---

    public function getPools(): array
    {
        return $this->conn->read('/ip/pool/print');
    }

    public function addPool(string $name, string $ranges, string $nextPool = ''): array
    {
        $params = [
            'name'   => $name,
            'ranges' => $ranges,
        ];
        if (!empty($nextPool)) $params['next-pool'] = $nextPool;
        return $this->conn->write('/ip/pool/add', $params);
    }

    public function removePool(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/ip/pool/print', $name) ?? $name);
        return $this->conn->remove('/ip/pool/remove', $id);
    }

    // --- DHCP SERVER & LEASES ---

    public function getDhcpServers(): array
    {
        return $this->conn->read('/ip/dhcp-server/print');
    }

    public function getDhcpLeases(): array
    {
        return $this->conn->read('/ip/dhcp-server/lease/print');
    }

    public function makeDhcpLeaseStatic(string $id): array
    {
        return $this->conn->write('/ip/dhcp-server/lease/make-static', ['.id' => $id]);
    }

    public function removeDhcpLease(string $id): array
    {
        return $this->conn->remove('/ip/dhcp-server/lease/remove', $id);
    }

    // --- ARP TABLE ---

    public function getArpTable(): array
    {
        return $this->conn->read('/ip/arp/print');
    }

    public function addArp(string $ip, string $mac, string $interface, string $comment = ''): array
    {
        $params = [
            'address'     => $ip,
            'mac-address' => $mac,
            'interface'   => $interface,
        ];
        if (!empty($comment)) $params['comment'] = $comment;
        return $this->conn->write('/ip/arp/add', $params);
    }

    public function removeArp(string $id): array
    {
        return $this->conn->remove('/ip/arp/remove', $id);
    }

    // --- STATIC DNS ---

    public function getStaticDns(): array
    {
        return $this->conn->read('/ip/dns/static/print');
    }

    public function addStaticDns(string $name, string $address, string $ttl = '1d'): array
    {
        return $this->conn->write('/ip/dns/static/add', [
            'name'    => $name,
            'address' => $address,
            'ttl'     => $ttl,
        ]);
    }

    public function removeStaticDns(string $id): array
    {
        return $this->conn->remove('/ip/dns/static/remove', $id);
    }
}
