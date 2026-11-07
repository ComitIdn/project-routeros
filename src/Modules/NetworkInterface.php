<?php

namespace RouterOSFramework\Modules;

use RouterOS\Query;
use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class NetworkInterface
 * Enterprise Server Interface monitoring: Ethernet, SFP/SFP+ Fiber, Bridges, VLANs,
 * Bonding (LACP Link Aggregation), VRRP (High-Availability Failover), WireGuard, and Traffic Telemetry.
 * Optimized for server-grade MikroTik routers (CCR, CRS, CHR, x86, RB1100, RB5009).
 */
class NetworkInterface
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * Get all active interfaces on the server.
     */
    public function getAll(): array
    {
        return $this->conn->read('/interface/print');
    }

    /**
     * Get physical Ethernet interfaces (ether1, ether2, etc.).
     */
    public function getEthernet(): array
    {
        return $this->conn->read('/interface/ethernet/print');
    }

    /**
     * Get SFP / SFP+ / QSFP fiber optic interfaces (common in CCR and CRS server routers).
     */
    public function getSfp(): array
    {
        return $this->conn->read('/interface/ethernet/print', ['combo-mode' => 'sfp']);
    }

    /**
     * Get Bridge interfaces.
     */
    public function getBridges(): array
    {
        return $this->conn->read('/interface/bridge/print');
    }

    /**
     * Get VLAN interfaces (802.1Q).
     */
    public function getVlans(): array
    {
        return $this->conn->read('/interface/vlan/print');
    }

    /**
     * Get Bonding interfaces (Link Aggregation / LACP 802.3ad).
     */
    public function getBonding(): array
    {
        return $this->conn->read('/interface/bonding/print');
    }

    /**
     * Get VRRP interfaces (Virtual Router Redundancy Protocol for Dual-Router High Availability).
     */
    public function getVrrp(): array
    {
        return $this->conn->read('/interface/vrrp/print');
    }

    /**
     * Get WireGuard interfaces.
     */
    public function getWireGuard(): array
    {
        return $this->conn->read('/interface/wireguard/print');
    }

    /**
     * Get EoIP (Ethernet over IP) server tunnel interfaces.
     */
    public function getEoip(): array
    {
        return $this->conn->read('/interface/eoip/print');
    }

    /**
     * Get GRE tunnel interfaces.
     */
    public function getGre(): array
    {
        return $this->conn->read('/interface/gre/print');
    }

    /**
     * Get VXLAN overlay interfaces (RouterOS v7+).
     */
    public function getVxlan(): array
    {
        return $this->conn->read('/interface/vxlan/print');
    }

    /**
     * Real-time traffic monitoring on any server interface (Ethernet, VLAN, Bonding, SFP).
     *
     * @param string $interface
     * @param int $once
     * @return array
     */
    public function monitorTraffic(string $interface, int $once = 1): array
    {
        $query = (new Query('/interface/monitor-traffic'))
            ->equal('interface', $interface)
            ->equal('once', (string)$once);
        $res = $this->conn->executeQuery($query);

        if (is_array($res) && !empty($res) && !isset($res['error'])) {
            foreach ($res as &$item) {
                if (isset($item['rx-bits-per-second'])) {
                    $item['rx_human'] = Helper::formatBytes(((float)$item['rx-bits-per-second']) / 8) . 'ps';
                }
                if (isset($item['tx-bits-per-second'])) {
                    $item['tx_human'] = Helper::formatBytes(((float)$item['tx-bits-per-second']) / 8) . 'ps';
                }
            }
        }
        return $res;
    }

    public function enable(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/interface/print', $name) ?? $name);
        return $this->conn->enable('/interface/enable', $id);
    }

    public function disable(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/interface/print', $name) ?? $name);
        return $this->conn->disable('/interface/disable', $id);
    }

    public function setComment(string $name, string $comment): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/interface/print', $name) ?? $name);
        return $this->conn->set('/interface/set', $id, ['comment' => $comment]);
    }
}
