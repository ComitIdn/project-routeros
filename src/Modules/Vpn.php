<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class Vpn
 * VPN management module: WireGuard peers & interfaces, L2TP, OpenVPN, and SSTP servers.
 */
class Vpn
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    // --- WIREGUARD ---

    public function getWireguardInterfaces(): array
    {
        return $this->conn->read('/interface/wireguard/print');
    }

    public function getWireguardPeers(): array
    {
        return $this->conn->read('/interface/wireguard/peers/print');
    }

    public function addWireguardPeer(
        string $interface,
        string $publicKey,
        string $allowedAddress,
        string $endpointAddress = '',
        int $endpointPort = 51820,
        string $comment = ''
    ): array {
        $params = [
            'interface'       => $interface,
            'public-key'      => $publicKey,
            'allowed-address' => $allowedAddress,
        ];
        if (!empty($endpointAddress)) {
            $params['endpoint-address'] = $endpointAddress;
            $params['endpoint-port'] = (string)$endpointPort;
        }
        if (!empty($comment)) {
            $params['comment'] = $comment;
        }

        $res = $this->conn->write('/interface/wireguard/peers/add', $params);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('VPN', 'Add WireGuard Peer', $interface, "Allowed: {$allowedAddress}", 'success');
        }

        return $res;
    }

    public function removeWireguardPeer(string $id): array
    {
        return $this->conn->remove('/interface/wireguard/peers/remove', $id);
    }

    // --- VPN SERVERS ---

    public function getL2tpServer(): array
    {
        return $this->conn->read('/interface/l2tp-server/server/print');
    }

    public function getOvpnServer(): array
    {
        return $this->conn->read('/interface/ovpn-server/server/print');
    }

    public function getSstpServer(): array
    {
        return $this->conn->read('/interface/sstp-server/server/print');
    }

    public function getActiveVpnSessions(): array
    {
        return $this->conn->read('/ppp/active/print');
    }
}
