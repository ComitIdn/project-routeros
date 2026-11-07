<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class Ptmp
 * Advanced Multi-PTMP (Point-to-Multipoint) Routeros Connection Module.
 * Supports MikroTik 802.11a/b/g/n/ac, NV2 TDMA, Nstreme, 60GHz (w60g), and WiFiWave2/WiFi 6.
 * Designed for WISP Towers, BTS APs, RT-RW Net Base Stations, and Client CPEs.
 */
class Ptmp
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * Detect wireless hardware and driver stacks installed on the RouterOS device.
     *
     * @return array
     */
    public function detectHardware(): array
    {
        $legacy = $this->conn->read('/interface/wireless/print');
        $w60g = $this->conn->read('/interface/w60g/print');
        $wifiwave2 = $this->conn->read('/interface/wifi/print');

        return [
            'has_legacy_wireless' => !empty($legacy) && !isset($legacy['error']),
            'has_60g_ptmp'        => !empty($w60g) && !isset($w60g['error']),
            'has_wifiwave2'       => !empty($wifiwave2) && !isset($wifiwave2['error']),
            'legacy_interfaces'   => !empty($legacy) && !isset($legacy['error']) ? array_column($legacy, 'name') : [],
            'w60g_interfaces'     => !empty($w60g) && !isset($w60g['error']) ? array_column($w60g, 'name') : [],
            'wifi_interfaces'     => !empty($wifiwave2) && !isset($wifiwave2['error']) ? array_column($wifiwave2, 'name') : [],
        ];
    }

    /**
     * Get all connected PTMP client CPE stations with complete signal telemetry.
     *
     * @param string|null $interface Filter by wireless interface (e.g. 'wlan1')
     * @return array
     */
    public function getStations(?string $interface = null): array
    {
        $where = [];
        if ($interface) {
            $where['interface'] = $interface;
        }

        $regTable = $this->conn->read('/interface/wireless/registration-table/print', $where);
        if (isset($regTable['error']) || !is_array($regTable)) {
            // Check 60GHz registration table if standard wireless is empty or error
            $w60gTable = $this->conn->read('/interface/w60g/station/print');
            if (!empty($w60gTable) && !isset($w60gTable['error'])) {
                return $this->format60gStations($w60gTable);
            }
            return [];
        }

        $stations = [];
        foreach ($regTable as $item) {
            $signal = isset($item['signal-strength']) ? (int)$item['signal-strength'] : -100;
            $txSignal = isset($item['signal-to-address']) ? (int)$item['signal-to-address'] : $signal;
            $rxCcq = isset($item['rx-ccq']) ? (int)$item['rx-ccq'] : 100;
            $txCcq = isset($item['tx-ccq']) ? (int)$item['tx-ccq'] : 100;

            // Health evaluation
            $health = 'Excellent';
            if ($signal < -80 || $txCcq < 60 || $rxCcq < 60) {
                $health = 'Critical / Degraded';
            } elseif ($signal < -72 || $txCcq < 80 || $rxCcq < 80) {
                $health = 'Fair';
            } elseif ($signal < -65) {
                $health = 'Good';
            }

            $stations[] = [
                'id'              => $item['.id'] ?? '',
                'mac_address'     => $item['mac-address'] ?? '',
                'interface'       => $item['interface'] ?? '',
                'ssid'            => $item['ssid'] ?? '',
                'signal_strength' => $signal . ' dBm',
                'signal_tx'       => $txSignal . ' dBm',
                'signal_rx'       => $signal . ' dBm',
                'snr'             => isset($item['signal-to-noise']) ? ($item['signal-to-noise'] . ' dB') : 'N/A',
                'ccq_tx'          => $txCcq . '%',
                'ccq_rx'          => $rxCcq . '%',
                'tx_rate'         => $item['tx-rate'] ?? '0Mbps',
                'rx_rate'         => $item['rx-rate'] ?? '0Mbps',
                'uptime'          => $item['uptime'] ?? '0s',
                'last_activity'   => $item['last-activity'] ?? '0s',
                'distance'        => isset($item['distance']) ? ($item['distance'] . ' km') : 'N/A',
                'routeros_version'=> $item['routeros-version'] ?? 'Unknown',
                'hardware'        => $item['radio-name'] ?? ($item['comment'] ?? ''),
                'health'          => $health,
                'comment'         => $item['comment'] ?? '',
            ];
        }

        return $stations;
    }

    /**
     * Format 60GHz PTMP stations.
     */
    private function format60gStations(array $stations): array
    {
        $formatted = [];
        foreach ($stations as $s) {
            $formatted[] = [
                'id'              => $s['.id'] ?? '',
                'mac_address'     => $s['mac-address'] ?? '',
                'interface'       => $s['interface'] ?? 'w60g',
                'ssid'            => $s['ssid'] ?? '60GHz-PTMP',
                'signal_strength' => ($s['signal'] ?? '0') . ' dBm',
                'signal_tx'       => ($s['tx-sector'] ?? 'N/A'),
                'signal_rx'       => ($s['rx-sector'] ?? 'N/A'),
                'snr'             => 'N/A',
                'ccq_tx'          => '100%',
                'ccq_rx'          => '100%',
                'tx_rate'         => ($s['tx-rate'] ?? '1Gbps'),
                'rx_rate'         => ($s['rx-rate'] ?? '1Gbps'),
                'uptime'          => $s['uptime'] ?? '0s',
                'last_activity'   => '0s',
                'distance'        => ($s['distance'] ?? 'N/A') . ' m',
                'routeros_version'=> 'v7+',
                'hardware'        => 'MikroTik 60GHz',
                'health'          => 'Excellent',
                'comment'         => $s['comment'] ?? '',
            ];
        }
        return $formatted;
    }

    /**
     * Get statistical health overview of PTMP connected stations.
     *
     * @param string|null $interface
     * @return array
     */
    public function getStationQualityMetrics(?string $interface = null): array
    {
        $stations = $this->getStations($interface);
        $total = count($stations);
        if ($total === 0) {
            return [
                'total_stations'   => 0,
                'average_signal'   => 'N/A',
                'degraded_count'   => 0,
                'optimal_count'    => 0,
                'health_percentage'=> 100,
            ];
        }

        $signalSum = 0;
        $degradedCount = 0;
        $optimalCount = 0;

        foreach ($stations as $s) {
            $sigVal = (int)str_replace(' dBm', '', $s['signal_strength']);
            $signalSum += $sigVal;

            if ($s['health'] === 'Critical / Degraded') {
                $degradedCount++;
            } elseif ($s['health'] === 'Excellent') {
                $optimalCount++;
            }
        }

        $avgSignal = round($signalSum / $total, 1);
        $healthPct = round((($total - $degradedCount) / $total) * 100, 1);

        return [
            'total_stations'    => $total,
            'average_signal'    => $avgSignal . ' dBm',
            'degraded_count'    => $degradedCount,
            'optimal_count'     => $optimalCount,
            'health_percentage' => $healthPct,
        ];
    }

    /**
     * Get PTMP Access List (MAC Whitelist/Blacklist rules).
     *
     * @return array
     */
    public function getAccessList(): array
    {
        return $this->conn->read('/interface/wireless/access-list/print');
    }

    /**
     * Add station to PTMP Access List (Whitelist or Blacklist).
     *
     * @param string $macAddress Station MAC address
     * @param string|null $interface Target wireless interface
     * @param string|null $signalRange Acceptable signal range e.g. '-80..120'
     * @param string|null $comment Label / Customer ID
     * @param bool $allow Whether to authenticate and forward
     * @return array
     */
    public function addAccessList(string $macAddress, ?string $interface = null, ?string $signalRange = null, ?string $comment = null, bool $allow = true): array
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

        $res = $this->conn->write('/interface/wireless/access-list/add', $params);
        if ($this->db && !isset($res['error'])) {
            $this->db->log('PTMP', 'Add Access List', $macAddress, "Allowed: " . ($allow ? 'yes' : 'no'), 'success');
        }
        return $res;
    }

    /**
     * Remove station from Access List.
     *
     * @param string $macOrId
     * @return array
     */
    public function removeAccessList(string $macOrId): array
    {
        $id = $macOrId;
        if (!str_starts_with($macOrId, '*')) {
            $cleanMac = strtoupper(str_replace(['-', '.'], ':', $macOrId));
            $found = $this->conn->read('/interface/wireless/access-list/print', ['mac-address' => $cleanMac]);
            if (!empty($found) && isset($found[0]['.id'])) {
                $id = $found[0]['.id'];
            }
        }

        return $this->conn->remove('/interface/wireless/access-list/remove', $id);
    }

    /**
     * Get CPE Connect List entries.
     *
     * @return array
     */
    public function getConnectList(): array
    {
        return $this->conn->read('/interface/wireless/connect-list/print');
    }

    /**
     * Add connect list entry for CPE station.
     */
    public function addConnectList(string $ssid, ?string $macAddress = null, ?string $securityProfile = null, ?string $interface = null): array
    {
        $params = ['ssid' => $ssid];
        if ($macAddress) {
            $params['mac-address'] = strtoupper(str_replace(['-', '.'], ':', $macAddress));
        }
        if ($securityProfile) {
            $params['security-profile'] = $securityProfile;
        }
        if ($interface) {
            $params['interface'] = $interface;
        }

        return $this->conn->write('/interface/wireless/connect-list/add', $params);
    }

    /**
     * Disconnect/kick a PTMP client station CPE.
     *
     * @param string $macAddress
     * @return array
     */
    public function kickStation(string $macAddress): array
    {
        $cleanMac = strtoupper(str_replace(['-', '.'], ':', $macAddress));
        $clients = $this->conn->read('/interface/wireless/registration-table/print', ['mac-address' => $cleanMac]);

        if (empty($clients) || isset($clients['error'])) {
            return ['kicked' => 0, 'status' => 'Station MAC not connected in registration table'];
        }

        $kicked = 0;
        foreach ($clients as $c) {
            if (isset($c['.id'])) {
                $this->conn->remove('/interface/wireless/registration-table/remove', $c['.id']);
                $kicked++;
            }
        }

        return ['kicked' => $kicked, 'mac' => $cleanMac];
    }

    /**
     * Set wireless protocol for an AP interface: 'nv2', 'nstreme', '802.11', or 'any'.
     *
     * @param string $interface e.g. 'wlan1'
     * @param string $protocol 'nv2' | 'nstreme' | '802.11' | 'any'
     * @return array
     */
    public function setProtocol(string $interface, string $protocol = 'nv2'): array
    {
        $id = str_starts_with($interface, '*') ? $interface : ($this->conn->findIdByName('/interface/wireless/print', $interface) ?? $interface);
        return $this->conn->set('/interface/wireless/set', $id, ['wireless-protocol' => strtolower($protocol)]);
    }

    /**
     * Configure NV2 TDMA protocol options on an interface.
     *
     * @param string $interface
     * @param array $options [
     *    'nv2-cell-radius'       => int (e.g. 10 to 100 km),
     *    'nv2-tdma-period-size'  => int (e.g. 2ms),
     *    'nv2-security'          => 'disabled' | 'key-management',
     *    'nv2-preshared-key'     => string,
     *    'nv2-queue-count'       => int (e.g. 4),
     * ]
     * @return array
     */
    public function setNv2Config(string $interface, array $options = []): array
    {
        $id = str_starts_with($interface, '*') ? $interface : ($this->conn->findIdByName('/interface/wireless/print', $interface) ?? $interface);
        return $this->conn->set('/interface/wireless/set', $id, $options);
    }

    /**
     * Change radio frequency and channel width.
     *
     * @param string $interface e.g. 'wlan1'
     * @param int|string $frequency e.g. 5180, 5200, 5745, 'auto'
     * @param string $band e.g. '5ghz-only-n', '5ghz-a/n/ac', '2ghz-b/g/n'
     * @param string $width e.g. '20mhz', '20/40mhz-XX', '20/40/80mhz-XXXX'
     * @return array
     */
    public function setChannel(string $interface, $frequency, string $band = '5ghz-only-n', string $width = '20/40mhz-XX'): array
    {
        $id = str_starts_with($interface, '*') ? $interface : ($this->conn->findIdByName('/interface/wireless/print', $interface) ?? $interface);
        return $this->conn->set('/interface/wireless/set', $id, [
            'frequency'     => (string)$frequency,
            'band'          => $band,
            'channel-width' => $width,
        ]);
    }

    /**
     * Configure Transmit Power (Tx Power) on PTMP interface.
     *
     * @param string $interface
     * @param int $txPowerDbm e.g. 17 to 30 dBm
     * @param string $mode 'all-rates-fixed' | 'manual-table' | 'default'
     * @return array
     */
    public function setTxPower(string $interface, int $txPowerDbm, string $mode = 'all-rates-fixed'): array
    {
        $id = str_starts_with($interface, '*') ? $interface : ($this->conn->findIdByName('/interface/wireless/print', $interface) ?? $interface);
        return $this->conn->set('/interface/wireless/set', $id, [
            'tx-power-mode' => $mode,
            'tx-power'      => (string)$txPowerDbm,
        ]);
    }

    /**
     * Scan wireless frequency spectrum to find clearest channels with lowest interference.
     *
     * @param string $interface
     * @param int $durationSeconds
     * @return array
     */
    public function scanFrequencies(string $interface, int $durationSeconds = 3): array
    {
        return $this->conn->read('/interface/wireless/frequency-monitor', [
            'interface' => $interface,
            'duration'  => "{$durationSeconds}s",
            'once'      => '',
        ]);
    }
}
