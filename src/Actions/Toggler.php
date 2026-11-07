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
     * Enable multiple items in batches.
     *
     * @param string $path
     * @param array $idsOrNames
     * @param int $chunkSize
     * @return array
     */
    public function enableBatch(string $path, array $idsOrNames, int $chunkSize = 50): array
    {
        $actionPath = $this->normalizeActionPath($path, 'enable');
        $resolved = [];
        foreach ($idsOrNames as $item) {
            $id = $this->resolveId($item, $path);
            if (!empty($id)) $resolved[] = $id;
        }

        $count = 0;
        foreach (array_chunk($resolved, max(1, $chunkSize)) as $chunk) {
            $this->conn->enable($actionPath, implode(',', $chunk));
            $count += count($chunk);
        }
        return ['action' => 'enable', 'count' => $count];
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
     * Disable multiple items in batches.
     *
     * @param string $path
     * @param array $idsOrNames
     * @param int $chunkSize
     * @return array
     */
    public function disableBatch(string $path, array $idsOrNames, int $chunkSize = 50): array
    {
        $actionPath = $this->normalizeActionPath($path, 'disable');
        $resolved = [];
        foreach ($idsOrNames as $item) {
            $id = $this->resolveId($item, $path);
            if (!empty($id)) $resolved[] = $id;
        }

        $count = 0;
        foreach (array_chunk($resolved, max(1, $chunkSize)) as $chunk) {
            $this->conn->disable($actionPath, implode(',', $chunk));
            $count += count($chunk);
        }
        return ['action' => 'disable', 'count' => $count];
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
