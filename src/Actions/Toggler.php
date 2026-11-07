<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Toggler
 * Dedicated operational class for enabling, disabling, toggling, and resetting item states.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Toggler
{
    private Connection $conn;
    private Finder $finder;

    public function __construct(Connection $conn, ?Finder $finder = null)
    {
        $this->conn = $conn;
        $this->finder = $finder ?? new Finder($conn);
    }

    private function resolveId(string $idOrName, string $path): ?string
    {
        if (str_starts_with($idOrName, '*')) {
            return $idOrName;
        }
        return $this->finder->nameToId($idOrName, $path) ?? $idOrName;
    }

    private function normalizeActionPath(string $path, string $action): string
    {
        $path = rtrim($path, '/');
        if (str_ends_with($path, '/print')) {
            $path = substr($path, 0, -6);
        }
        return $path . '/' . $action;
    }

    /**
     * Enable an item.
     *
     * @param string $path
     * @param string $idOrName
     * @return array
     */
    public function enable(string $path, string $idOrName): array
    {
        $id = $this->resolveId($idOrName, $path);
        $actionPath = $this->normalizeActionPath($path, 'enable');
        return $this->conn->enable($actionPath, $id);
    }

    /**
     * Disable an item.
     *
     * @param string $path
     * @param string $idOrName
     * @return array
     */
    public function disable(string $path, string $idOrName): array
    {
        $id = $this->resolveId($idOrName, $path);
        $actionPath = $this->normalizeActionPath($path, 'disable');
        return $this->conn->disable($actionPath, $id);
    }

    /**
     * Toggle item disabled/enabled status.
     *
     * @param string $path
     * @param string $idOrName
     * @return array
     */
    public function toggle(string $path, string $idOrName): array
    {
        $item = str_starts_with($idOrName, '*')
            ? $this->finder->byId($idOrName, $path)
            : $this->finder->byName($idOrName, $path);

        $isDisabled = ($item['disabled'] ?? 'false') === 'true';
        return $isDisabled ? $this->enable($path, $idOrName) : $this->disable($path, $idOrName);
    }

    /**
     * Reset counters (bytes/packets/uptime) on an item.
     *
     * @param string $path
     * @param string $idOrName
     * @return array
     */
    public function resetCounter(string $path, string $idOrName): array
    {
        $id = $this->resolveId($idOrName, $path);
        $actionPath = $this->normalizeActionPath($path, 'reset-counters');
        return $this->conn->write($actionPath, ['.id' => $id]);
    }
}
