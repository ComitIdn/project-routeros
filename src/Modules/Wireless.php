<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class Wireless
 * MikroTik Wi-Fi management module: Registration table, signal strength, SSID, security profiles, and client kick.
 */
class Wireless
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    public function getInterfaces(): array
    {
        return $this->conn->read('/interface/wireless/print');
    }

    /**
     * Get WiFi client registration table (connected wireless devices).
     *
     * @return array
     */
    public function getRegistrationTable(): array
    {
        return $this->conn->read('/interface/wireless/registration-table/print');
    }

    /**
     * Get connected WiFi clients with human-friendly signal formatting and data rates.
     *
     * @return array
     */
    public function getConnectedClients(): array
    {
        $reg = $this->getRegistrationTable();
        if (isset($reg['error']) || !is_array($reg)) {
            return $reg;
        }

        $formatted = [];
        foreach ($reg as $client) {
            $signal = isset($client['signal-strength']) ? (int)$client['signal-strength'] : -100;
            $quality = 'Poor';
            if ($signal >= -60) {
                $quality = 'Excellent';
            } elseif ($signal >= -70) {
                $quality = 'Good';
            } elseif ($signal >= -80) {
                $quality = 'Fair';
            }

            $bytesIn = isset($client['bytes'][0]) ? (float)$client['bytes'][0] : 0;
            $bytesOut = isset($client['bytes'][1]) ? (float)$client['bytes'][1] : 0;

            $formatted[] = [
                'id'              => $client['.id'] ?? '',
                'mac_address'     => $client['mac-address'] ?? '',
                'interface'       => $client['interface'] ?? '',
                'signal_strength' => $signal . ' dBm',
                'signal_quality'  => $quality,
                'tx_rate'         => $client['tx-rate'] ?? '',
                'rx_rate'         => $client['rx-rate'] ?? '',
                'uptime'          => $client['uptime'] ?? '0s',
                'comment'         => $client['comment'] ?? '',
            ];
        }

        return $formatted;
    }

    public function getSecurityProfiles(): array
    {
        return $this->conn->read('/interface/wireless/security-profiles/print');
    }

    /**
     * Change Wi-Fi WPA2 pre-shared key for a security profile.
     *
     * @param string $profile Name of security profile (default 'default')
     * @param string $newPassword New Wi-Fi password
     * @return array
     */
    public function setWifiPassword(string $profile, string $newPassword): array
    {
        $id = str_starts_with($profile, '*') ? $profile : ($this->conn->findIdByName('/interface/wireless/security-profiles/print', $profile) ?? $profile);
        $res = $this->conn->set('/interface/wireless/security-profiles/set', $id, [
            'wpa2-pre-shared-key' => $newPassword,
            'wpa-pre-shared-key'  => $newPassword,
        ]);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('Wireless', 'Change WiFi Password', $profile, 'WiFi password updated', 'success');
        }

        return $res;
    }

    /**
     * Change SSID of a wireless interface.
     *
     * @param string $interface e.g. 'wlan1'
     * @param string $ssid New SSID name
     * @return array
     */
    public function setSsid(string $interface, string $ssid): array
    {
        $id = str_starts_with($interface, '*') ? $interface : ($this->conn->findIdByName('/interface/wireless/print', $interface) ?? $interface);
        return $this->conn->set('/interface/wireless/set', $id, ['ssid' => $ssid]);
    }

    /**
     * Kick / disconnect a wireless client from AP.
     *
     * @param string $macAddress
     * @return array
     */
    public function disconnectClient(string $macAddress): array
    {
        $cleanMac = Helper::formatMac($macAddress);
        $clients = $this->conn->read('/interface/wireless/registration-table/print', ['mac-address' => $cleanMac]);

        if (empty($clients) || isset($clients['error'])) {
            return ['disconnected' => 0, 'status' => 'Client MAC not connected to Wi-Fi'];
        }

        $count = 0;
        foreach ($clients as $c) {
            if (isset($c['.id'])) {
                $this->conn->remove('/interface/wireless/registration-table/remove', $c['.id']);
                $count++;
            }
        }
        return ['disconnected' => $count, 'mac' => $cleanMac];
    }
}
