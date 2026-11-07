<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Setter
 * Dedicated operational class for modifying, updating, and setting RouterOS properties.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Setter
{
    private Connection $conn;
    private Finder $finder;

    public function __construct(Connection $conn, ?Finder $finder = null)
    {
        $this->conn = $conn;
        $this->finder = $finder ?? new Finder($conn);
    }

    /**
     * Normalize path to end with /set.
     */
    private function normalizeSetPath(string $path): string
    {
        $path = rtrim($path, '/');
        if (str_ends_with($path, '/print')) {
            $path = substr($path, 0, -6);
        }
        if (!str_ends_with($path, '/set')) {
            $path .= '/set';
        }
        return $path;
    }

    /**
     * Resolve ID from name or return ID as-is.
     */
    private function resolveId(string $idOrName, string $path): ?string
    {
        if (str_starts_with($idOrName, '*')) {
            return $idOrName;
        }
        return $this->finder->nameToId($idOrName, $path) ?? $idOrName;
    }

    /**
     * Universal set method for any RouterOS path and parameters.
     *
     * @param string $path Target path e.g. '/ip/hotspot/user'
     * @param string $idOrName Internal .id or item name
     * @param array $params Properties to update
     * @return array
     */
    public function set(string $path, string $idOrName, array $params): array
    {
        $id = $this->resolveId($idOrName, $path);
        $setPath = $this->normalizeSetPath($path);
        return $this->conn->set($setPath, $id, $params);
    }

    /**
     * Update uptime or data quota limits.
     *
     * @param string $idOrName Username or .id
     * @param string|null $limitUptime e.g. '2h', '1d', '00:30:00'
     * @param string|int|null $limitBytes Total byte limit (e.g. 1073741824 or '1G')
     * @param string $path Default '/ip/hotspot/user'
     * @return array
     */
    public function limit(string $idOrName, ?string $limitUptime = null, $limitBytes = null, string $path = '/ip/hotspot/user'): array
    {
        $params = [];
        if ($limitUptime !== null) {
            $params['limit-uptime'] = $limitUptime;
        }
        if ($limitBytes !== null) {
            $params['limit-bytes-total'] = (string)$limitBytes;
        }
        return $this->set($path, $idOrName, $params);
    }

    public function setlimit(string $idOrName, ?string $limitUptime = null, $limitBytes = null, string $path = '/ip/hotspot/user'): array
    {
        return $this->limit($idOrName, $limitUptime, $limitBytes, $path);
    }

    /**
     * Update comment field.
     *
     * @param string $idOrName
     * @param string $comment
     * @param string $path
     * @return array
     */
    public function comment(string $idOrName, string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->set($path, $idOrName, ['comment' => $comment]);
    }

    public function setcoment(string $idOrName, string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->comment($idOrName, $comment, $path);
    }

    public function setcomment(string $idOrName, string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->comment($idOrName, $comment, $path);
    }

    /**
     * Update email field.
     *
     * @param string $idOrName
     * @param string $email
     * @param string $path
     * @return array
     */
    public function email(string $idOrName, string $email, string $path = '/ip/hotspot/user'): array
    {
        return $this->set($path, $idOrName, ['email' => $email]);
    }

    public function setemail(string $idOrName, string $email, string $path = '/ip/hotspot/user'): array
    {
        return $this->email($idOrName, $email, $path);
    }

    /**
     * Rename an item (change 'name' attribute).
     *
     * @param string $idOrOldName
     * @param string $newName
     * @param string $path
     * @return array
     */
    public function name(string $idOrOldName, string $newName, string $path = '/ip/hotspot/user'): array
    {
        return $this->set($path, $idOrOldName, ['name' => $newName]);
    }

    public function setname(string $idOrOldName, string $newName, string $path = '/ip/hotspot/user'): array
    {
        return $this->name($idOrOldName, $newName, $path);
    }

    /**
     * Update password field.
     *
     * @param string $idOrName
     * @param string $password
     * @param string $path
     * @return array
     */
    public function password(string $idOrName, string $password, string $path = '/ip/hotspot/user'): array
    {
        return $this->set($path, $idOrName, ['password' => $password]);
    }

    public function setpassword(string $idOrName, string $password, string $path = '/ip/hotspot/user'): array
    {
        return $this->password($idOrName, $password, $path);
    }

    /**
     * Update profile field.
     *
     * @param string $idOrName
     * @param string $profile
     * @param string $path
     * @return array
     */
    public function profile(string $idOrName, string $profile, string $path = '/ip/hotspot/user'): array
    {
        return $this->set($path, $idOrName, ['profile' => $profile]);
    }

    public function setprofile(string $idOrName, string $profile, string $path = '/ip/hotspot/user'): array
    {
        return $this->profile($idOrName, $profile, $path);
    }

    /**
     * Update MAC address lock field.
     *
     * @param string $idOrName
     * @param string $macAddress
     * @param string $path
     * @return array
     */
    public function macAddress(string $idOrName, string $macAddress, string $path = '/ip/hotspot/user'): array
    {
        return $this->set($path, $idOrName, ['mac-address' => $macAddress]);
    }

    public function setmac(string $idOrName, string $macAddress, string $path = '/ip/hotspot/user'): array
    {
        return $this->macAddress($idOrName, $macAddress, $path);
    }

    /**
     * Update any single arbitrary property dynamically.
     *
     * @param string $path
     * @param string $idOrName
     * @param string $property
     * @param mixed $value
     * @return array
     */
    public function property(string $path, string $idOrName, string $property, $value): array
    {
        return $this->set($path, $idOrName, [$property => $value]);
    }
}
