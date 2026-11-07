<?php

namespace RouterOSFramework\Modules;

use RouterOS\Query;
use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class Tool
 * RouterOS network tools: Wake-on-LAN, Email, SMS, Traceroute, IP Scan, and Bandwidth Test.
 */
class Tool
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * Send Wake-on-LAN magic packet to wake up remote PC or server.
     *
     * @param string $macAddress Target PC MAC address
     * @param string $interface Interface where PC is connected (default 'ether1')
     * @return array
     */
    public function wakeOnLan(string $macAddress, string $interface = 'ether1'): array
    {
        $cleanMac = Helper::formatMac($macAddress);
        $query = (new Query('/tool/wol'))
            ->equal('mac', $cleanMac)
            ->equal('interface', $interface);

        $res = $this->conn->executeQuery($query);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('Tool', 'Wake-on-LAN', $cleanMac, "WOL sent on {$interface}", 'success');
        }

        return $res;
    }

    /**
     * Send email notification directly from MikroTik router.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email content
     * @param string|null $server Optional SMTP server IP/host
     * @param int|null $port Optional SMTP port
     * @return array
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $body,
        ?string $server = null,
        ?int $port = null
    ): array {
        $query = (new Query('/tool/e-mail/send'))
            ->equal('to', $to)
            ->equal('subject', $subject)
            ->equal('body', $body);

        if (!empty($server)) $query->equal('server', $server);
        if (!empty($port)) $query->equal('port', (string)$port);

        return $this->conn->executeQuery($query);
    }

    /**
     * Send SMS message via GSM/LTE modem attached to MikroTik.
     *
     * @param string $phoneNumber Target phone number
     * @param string $message Text message
     * @param string $port Modem USB port (default 'usb1' or 'lte1')
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message, string $port = 'usb1'): array
    {
        $query = (new Query('/tool/sms/send'))
            ->equal('phone-number', $phoneNumber)
            ->equal('message', $message)
            ->equal('port', $port);

        return $this->conn->executeQuery($query);
    }

    /**
     * Execute Traceroute to inspect network route hops.
     *
     * @param string $address Target IP or domain
     * @param int $count Number of probe packets
     * @return array
     */
    public function traceroute(string $address, int $count = 1): array
    {
        $query = (new Query('/tool/traceroute'))
            ->equal('address', $address)
            ->equal('count', (string)$count);

        return $this->conn->executeQuery($query);
    }

    /**
     * Scan active IP addresses on an interface.
     *
     * @param string $interface
     * @param string $duration e.g. '5s'
     * @return array
     */
    public function ipScan(string $interface, string $duration = '5s'): array
    {
        $query = (new Query('/tool/ip-scan'))
            ->equal('interface', $interface)
            ->equal('duration', $duration);

        return $this->conn->executeQuery($query);
    }

    /**
     * Run Bandwidth Speed Test against another MikroTik router.
     *
     * @param string $address Remote MikroTik IP
     * @param string $user Remote username
     * @param string $password Remote password
     * @param string $direction ('both'|'receive'|'transmit')
     * @param string $duration Duration (default '5s')
     * @return array
     */
    public function bandwidthTest(
        string $address,
        string $user = 'admin',
        string $password = '',
        string $direction = 'both',
        string $duration = '5s'
    ): array {
        $query = (new Query('/tool/bandwidth-test'))
            ->equal('address', $address)
            ->equal('user', $user)
            ->equal('password', $password)
            ->equal('direction', $direction)
            ->equal('duration', $duration);

        return $this->conn->executeQuery($query);
    }
}
