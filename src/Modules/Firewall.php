<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class Firewall
 * Full MikroTik Firewall management module (Filter, NAT, Mangle, RAW, Address Lists).
 */
class Firewall
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    // --- FILTER RULES ---

    public function getFilterRules(): array
    {
        return $this->conn->read('/ip/firewall/filter/print');
    }

    public function addFilterRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/filter/add', $params);
    }

    public function removeFilterRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/filter/remove', $id);
    }

    // --- NAT RULES ---

    public function getNatRules(): array
    {
        return $this->conn->read('/ip/firewall/nat/print');
    }

    public function addNatRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/nat/add', $params);
    }

    public function addMasquerade(string $outInterface, string $comment = 'Default Masquerade NAT'): array
    {
        return $this->addNatRule([
            'chain'         => 'srcnat',
            'action'        => 'masquerade',
            'out-interface' => $outInterface,
            'comment'       => $comment,
        ]);
    }

    public function removeNatRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/nat/remove', $id);
    }

    // --- MANGLE RULES ---

    public function getMangleRules(): array
    {
        return $this->conn->read('/ip/firewall/mangle/print');
    }

    public function addMangleRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/mangle/add', $params);
    }

    public function removeMangleRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/mangle/remove', $id);
    }

    // --- RAW RULES ---

    public function getRawRules(): array
    {
        return $this->conn->read('/ip/firewall/raw/print');
    }

    public function addRawRule(array $params): array
    {
        return $this->conn->write('/ip/firewall/raw/add', $params);
    }

    public function removeRawRule(string $id): array
    {
        return $this->conn->remove('/ip/firewall/raw/remove', $id);
    }

    // --- ADDRESS LISTS ---

    public function getAddressLists(): array
    {
        return $this->conn->read('/ip/firewall/address-list/print');
    }

    public function addAddressList(string $list, string $address, string $comment = '', string $timeout = ''): array
    {
        $params = [
            'list'    => $list,
            'address' => $address,
        ];
        if (!empty($comment)) $params['comment'] = $comment;
        if (!empty($timeout)) $params['timeout'] = $timeout;

        return $this->conn->write('/ip/firewall/address-list/add', $params);
    }

    public function removeAddressList(string $id): array
    {
        return $this->conn->remove('/ip/firewall/address-list/remove', $id);
    }

    // --- ENTERPRISE SECURITY & SERVER HARDENING ---

    /**
     * Enable RouterOS FastTrack connection acceleration.
     * Bypasses heavy firewall inspection for established and related packets,
     * drastically lowering CPU usage by up to 80% on high bandwidth throughput.
     *
     * @param string $comment
     * @return array
     */
    public function enableFastTrack(string $comment = 'Project RouterOS - FastTrack Acceleration'): array
    {
        // 1. Accept established,related in forward
        $this->addFilterRule([
            'chain'            => 'forward',
            'action'           => 'fasttrack-connection',
            'connection-state' => 'established,related',
            'comment'          => $comment,
        ]);

        return $this->addFilterRule([
            'chain'            => 'forward',
            'action'           => 'accept',
            'connection-state' => 'established,related',
            'comment'          => 'Accept Established/Related after FastTrack',
        ]);
    }

    /**
     * Drop invalid state packets on input and forward chains.
     * Protects against spoofed and malformed packets.
     */
    public function dropInvalid(): array
    {
        $r1 = $this->addFilterRule([
            'chain'            => 'input',
            'action'           => 'drop',
            'connection-state' => 'invalid',
            'comment'          => 'Drop Invalid Input Packets',
        ]);
        $r2 = $this->addFilterRule([
            'chain'            => 'forward',
            'action'           => 'drop',
            'connection-state' => 'invalid',
            'comment'          => 'Drop Invalid Forward Packets',
        ]);
        return ['input' => $r1, 'forward' => $r2];
    }

    /**
     * Deploy 3-stage brute-force attack defense for SSH (22), API (8728/8729), and WinBox (8291).
     * Repeated failed login attempts are automatically quarantined into a 7-day blacklist.
     *
     * @param array $ports TCP destination ports to protect (default [22, 8291, 8728, 8729])
     * @param int $stageTimeoutMinutes Detection window in minutes (default 3m)
     * @param int $blacklistDays Quarantine duration in days (default 7d)
     * @return array
     */
    public function protectBruteForce(
        array $ports = [22, 8291, 8728, 8729],
        int $stageTimeoutMinutes = 3,
        int $blacklistDays = 7
    ): array {
        $portStr = implode(',', $ports);
        $stageTimeout = "{$stageTimeoutMinutes}m";
        $blacklistTimeout = "{$blacklistDays}d";

        $results = [];

        // 1. Drop blacklisted attackers immediately
        $results[] = $this->addFilterRule([
            'chain'             => 'input',
            'action'            => 'drop',
            'src-address-list'  => 'bruteforce_blacklist',
            'comment'           => 'Drop Brute-Force Blacklisted IPs',
        ]);

        // 2. Stage 3 -> Blacklist
        $results[] = $this->addFilterRule([
            'chain'             => 'input',
            'action'            => 'add-src-to-address-list',
            'protocol'          => 'tcp',
            'dst-port'          => $portStr,
            'connection-state'  => 'new',
            'src-address-list'  => 'bruteforce_stage3',
            'address-list'      => 'bruteforce_blacklist',
            'address-list-timeout' => $blacklistTimeout,
            'comment'           => 'Brute-Force Stage 3 -> Blacklist',
        ]);

        // 3. Stage 2 -> Stage 3
        $results[] = $this->addFilterRule([
            'chain'             => 'input',
            'action'            => 'add-src-to-address-list',
            'protocol'          => 'tcp',
            'dst-port'          => $portStr,
            'connection-state'  => 'new',
            'src-address-list'  => 'bruteforce_stage2',
            'address-list'      => 'bruteforce_stage3',
            'address-list-timeout' => $stageTimeout,
            'comment'           => 'Brute-Force Stage 2 -> Stage 3',
        ]);

        // 4. Stage 1 -> Stage 2
        $results[] = $this->addFilterRule([
            'chain'             => 'input',
            'action'            => 'add-src-to-address-list',
            'protocol'          => 'tcp',
            'dst-port'          => $portStr,
            'connection-state'  => 'new',
            'src-address-list'  => 'bruteforce_stage1',
            'address-list'      => 'bruteforce_stage2',
            'address-list-timeout' => $stageTimeout,
            'comment'           => 'Brute-Force Stage 1 -> Stage 2',
        ]);

        // 5. Initial new connection -> Stage 1
        $results[] = $this->addFilterRule([
            'chain'             => 'input',
            'action'            => 'add-src-to-address-list',
            'protocol'          => 'tcp',
            'dst-port'          => $portStr,
            'connection-state'  => 'new',
            'address-list'      => 'bruteforce_stage1',
            'address-list-timeout' => $stageTimeout,
            'comment'           => 'Brute-Force New Conn -> Stage 1',
        ]);

        return [
            'status'  => 'active',
            'ports'   => $ports,
            'rules'   => count($results),
        ];
    }

    /**
     * Protect against stealth port scanners (Nmap, Masscan, PSD).
     */
    public function protectPortScanners(): array
    {
        $results = [];

        // Drop already detected port scanners
        $results[] = $this->addFilterRule([
            'chain'            => 'input',
            'action'           => 'drop',
            'src-address-list' => 'port_scanners',
            'comment'          => 'Drop Port Scanners',
        ]);

        // Detect port scanner via PSD (Port Scan Detection)
        $results[] = $this->addFilterRule([
            'chain'                => 'input',
            'action'               => 'add-src-to-address-list',
            'protocol'             => 'tcp',
            'psd'                  => '21,3s,3,1',
            'address-list'         => 'port_scanners',
            'address-list-timeout' => '14d',
            'comment'              => 'Detect TCP Port Scanner',
        ]);

        return ['status' => 'active', 'rules' => count($results)];
    }

    /**
     * Deploy comprehensive 1-click enterprise server firewall baseline.
     * Sets up FastTrack, drops invalid packets, activates brute-force protection,
     * and blocks port scanners.
     *
     * @param string $lanInterface
     * @param string|null $wanInterface
     * @return array
     */
    public function hardenServerBaseline(string $lanInterface = 'bridge', ?string $wanInterface = null): array
    {
        $report = [];
        $report['fasttrack'] = $this->enableFastTrack();
        $report['drop_invalid'] = $this->dropInvalid();
        $report['bruteforce'] = $this->protectBruteForce();
        $report['port_scanners'] = $this->protectPortScanners();

        if ($this->db) {
            $this->db->log('Firewall', 'Harden Server', $lanInterface, 'Applied baseline enterprise security hardening', 'success');
        }

        return [
            'status'  => 'hardened',
            'modules' => array_keys($report),
            'details' => $report,
        ];
    }
}
