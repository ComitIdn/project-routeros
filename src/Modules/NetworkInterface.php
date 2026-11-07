<?php

namespace RouterOSFramework\Modules;

use RouterOS\Query;
use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class NetworkInterface
 * Interface monitoring, bridge, wireless, wireguard, and traffic management.
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

    public function getAll(): array
    {
        return $this->conn->read('/interface/print');
    }

    public function getEthernet(): array
    {
        return $this->conn->read('/interface/ethernet/print');
    }

    public function getBridges(): array
    {
        return $this->conn->read('/interface/bridge/print');
    }

    public function getWireless(): array
    {
        return $this->conn->read('/interface/wireless/print');
    }

    public function getWireGuard(): array
    {
        return $this->conn->read('/interface/wireguard/print');
    }

    public function getVlans(): array
    {
        return $this->conn->read('/interface/vlan/print');
    }

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
