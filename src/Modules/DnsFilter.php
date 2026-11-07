<?php

namespace RouterOSFramework\Modules;

use RouterOS\Query;
use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;

/**
 * Class DnsFilter
 * DNS AdBlocker, static domain blocker, and DNS cache management module.
 */
class DnsFilter
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * Block a domain via static DNS redirect (e.g. to 0.0.0.0 or 127.0.0.1).
     *
     * @param string $domain
     * @param string $redirectIp
     * @param string $comment
     * @return array
     */
    public function blockDomain(string $domain, string $redirectIp = '0.0.0.0', string $comment = 'AdBlock'): array
    {
        $res = $this->conn->write('/ip/dns/static/add', [
            'name'    => $domain,
            'address' => $redirectIp,
            'comment' => $comment,
            'ttl'     => '1d',
        ]);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('DnsFilter', 'Block Domain', $domain, "Redirected to {$redirectIp}", 'success');
        }

        return $res;
    }

    /**
     * Unblock a domain from static DNS.
     *
     * @param string $domain
     * @return array
     */
    public function unblockDomain(string $domain): array
    {
        $entries = $this->conn->read('/ip/dns/static/print', ['name' => $domain]);
        if (empty($entries) || isset($entries['error'])) {
            return ['unblocked' => 0, 'status' => 'Domain not found in static DNS'];
        }

        $count = 0;
        foreach ($entries as $e) {
            if (isset($e['.id'])) {
                $this->conn->remove('/ip/dns/static/remove', $e['.id']);
                $count++;
            }
        }
        return ['unblocked' => $count, 'domain' => $domain];
    }

    /**
     * Flush / clear all MikroTik DNS cache.
     *
     * @return array
     */
    public function flushCache(): array
    {
        return $this->conn->write('/ip/dns/cache/flush');
    }

    /**
     * Import a list of ad/malware domains into static DNS.
     *
     * @param array $domains
     * @param string $redirectIp
     * @param string $tag
     * @return array
     */
    public function importHostsBlocklist(array $domains, string $redirectIp = '0.0.0.0', string $tag = 'Blocklist'): array
    {
        $count = 0;
        foreach ($domains as $d) {
            $d = trim($d);
            if (!empty($d) && !str_starts_with($d, '#')) {
                $this->blockDomain($d, $redirectIp, $tag);
                $count++;
            }
        }
        return ['imported' => $count, 'tag' => $tag];
    }
}
