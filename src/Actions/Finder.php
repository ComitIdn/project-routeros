<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Finder
 * Dedicated operational class for querying, searching, and mapping RouterOS names to IDs.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Finder
{
    private Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Normalize endpoint path to always end with /print.
     */
    private function normalizePrintPath(string $path): string
    {
        $path = rtrim($path, '/');
        if (!str_ends_with($path, '/print')) {
            $path .= '/print';
        }
        return $path;
    }

    /**
     * Resolve a RouterOS item name to its internal .id (e.g. *1F).
     * If $name already starts with '*', it is returned directly.
     *
     * @param string $name
     * @param string $path Default '/ip/hotspot/user'
     * @return string|null
     */
    public function nameToId(string $name, string $path = '/ip/hotspot/user'): ?string
    {
        if (str_starts_with($name, '*')) {
            return $name;
        }

        $printPath = $this->normalizePrintPath($path);
        return $this->conn->findIdByName($printPath, $name);
    }

    /**
     * Alias for nameToId for backward and snake_case compatibility.
     */
    public function findname_toId(string $name, string $path = '/ip/hotspot/user'): ?string
    {
        return $this->nameToId($name, $path);
    }

    /**
     * Resolve internal .id to its human name.
     *
     * @param string $id
     * @param string $path
     * @return string|null
     */
    public function idToName(string $id, string $path = '/ip/hotspot/user'): ?string
    {
        $item = $this->byId($id, $path);
        return $item['name'] ?? null;
    }

    /**
     * Find a single item by its 'name' attribute.
     *
     * @param string $name
     * @param string $path
     * @return array|null
     */
    public function byName(string $name, string $path = '/ip/hotspot/user'): ?array
    {
        $printPath = $this->normalizePrintPath($path);
        $res = $this->conn->read($printPath, ['name' => $name]);
        if (!empty($res) && is_array($res) && !isset($res['error'])) {
            return $res[0] ?? null;
        }
        return null;
    }

    /**
     * Find a single item by its internal '.id'.
     *
     * @param string $id
     * @param string $path
     * @return array|null
     */
    public function byId(string $id, string $path = '/ip/hotspot/user'): ?array
    {
        $printPath = $this->normalizePrintPath($path);
        $res = $this->conn->read($printPath, ['.id' => $id]);
        if (!empty($res) && is_array($res) && !isset($res['error'])) {
            return $res[0] ?? null;
        }
        return null;
    }

    /**
     * Find item by MAC address (useful for host, dhcp-lease, wireless).
     *
     * @param string $macAddress
     * @param string $path Default '/ip/hotspot/host'
     * @return array|null
     */
    public function byMac(string $macAddress, string $path = '/ip/hotspot/host'): ?array
    {
        $printPath = $this->normalizePrintPath($path);
        $res = $this->conn->read($printPath, ['mac-address' => $macAddress]);
        if (!empty($res) && is_array($res) && !isset($res['error'])) {
            return $res[0] ?? null;
        }
        return null;
    }

    /**
     * Find item by IP address (useful for active sessions, arp, leases).
     *
     * @param string $ip
     * @param string $path Default '/ip/hotspot/active'
     * @return array|null
     */
    public function byIp(string $ip, string $path = '/ip/hotspot/active'): ?array
    {
        $printPath = $this->normalizePrintPath($path);
        $res = $this->conn->read($printPath, ['address' => $ip]);
        if (!empty($res) && is_array($res) && !isset($res['error'])) {
            return $res[0] ?? null;
        }
        return null;
    }

    /**
     * Find items matching a comment pattern or exact comment.
     *
     * @param string $comment
     * @param string $path
     * @return array
     */
    public function byComment(string $comment, string $path = '/ip/hotspot/user'): array
    {
        $printPath = $this->normalizePrintPath($path);
        $res = $this->conn->read($printPath, ['comment' => $comment]);
        return (!empty($res) && is_array($res) && !isset($res['error'])) ? $res : [];
    }

    /**
     * Generic query matching an array of criteria.
     *
     * @param string $path
     * @param array $criteria Key-value pairs
     * @return array
     */
    public function where(string $path, array $criteria): array
    {
        $printPath = $this->normalizePrintPath($path);
        $res = $this->conn->read($printPath, $criteria);
        return (!empty($res) && is_array($res) && !isset($res['error'])) ? $res : [];
    }

    /**
     * Generic query returning the first matching item.
     *
     * @param string $path
     * @param array $criteria
     * @return array|null
     */
    public function first(string $path, array $criteria): ?array
    {
        $items = $this->where($path, $criteria);
        return $items[0] ?? null;
    }

    /**
     * Check if at least one matching item exists.
     *
     * @param string $path
     * @param array $criteria
     * @return bool
     */
    public function exists(string $path, array $criteria): bool
    {
        return $this->first($path, $criteria) !== null;
    }
}
