<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Reader
 * Dedicated operational class for reading, streaming, and monitoring RouterOS data.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Reader
{
    private Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Read records from any print endpoint.
     *
     * @param string $path Target path e.g. '/ip/hotspot/user/print' or '/ip/address'
     * @param array $where Filter conditions
     * @return array
     */
    public function read(string $path, array $where = []): array
    {
        $path = rtrim($path, '/');
        if (!str_ends_with($path, '/print')) {
            $path .= '/print';
        }
        return $this->conn->read($path, $where);
    }

    /**
     * Read the first matching record or null.
     *
     * @param string $path
     * @param array $where
     * @return array|null
     */
    public function first(string $path, array $where = []): ?array
    {
        $res = $this->read($path, $where);
        return (!empty($res) && is_array($res) && isset($res[0]) && !isset($res['error'])) ? $res[0] : null;
    }

    /**
     * Monitor real-time traffic on an interface.
     *
     * @param string $interface Interface name e.g. 'ether1'
     * @param int $duration Number of seconds / samples
     * @return array
     */
    public function monitorTraffic(string $interface, int $duration = 1): array
    {
        return $this->conn->read('/interface/monitor-traffic', [
            'interface' => $interface,
            'once'      => '',
        ]);
    }

    /**
     * Get RX / TX byte and packet statistics for an interface.
     *
     * @param string $interface
     * @return array
     */
    public function stats(string $interface): array
    {
        $data = $this->first('/interface', ['name' => $interface]);
        if (!$data) {
            return ['error' => "Interface {$interface} not found"];
        }

        return [
            'interface' => $interface,
            'rx_bytes'  => (int)($data['rx-byte'] ?? 0),
            'tx_bytes'  => (int)($data['tx-byte'] ?? 0),
            'rx_packets'=> (int)($data['rx-packet'] ?? 0),
            'tx_packets'=> (int)($data['tx-packet'] ?? 0),
            'rx_errors' => (int)($data['rx-error'] ?? 0),
            'tx_errors' => (int)($data['tx-error'] ?? 0),
            'rx_drops'  => (int)($data['rx-drop'] ?? 0),
            'tx_drops'  => (int)($data['tx-drop'] ?? 0),
            'running'   => ($data['running'] ?? 'false') === 'true',
            'disabled'  => ($data['disabled'] ?? 'false') === 'true',
        ];
    }

    /**
     * Read real-time wireless registration table with active signal statistics.
     *
     * @param string|null $interface Optional filter by interface name
     * @return array
     */
    public function streamWireless(?string $interface = null): array
    {
        $where = [];
        if ($interface) {
            $where['interface'] = $interface;
        }
        return $this->read('/interface/wireless/registration-table', $where);
    }
}
