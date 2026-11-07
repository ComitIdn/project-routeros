<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Remover
 * Dedicated operational class for deleting items by name, ID, or bulk condition.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Remover
{
    private Connection $conn;
    private Finder $finder;

    public function __construct(Connection $conn, ?Finder $finder = null)
    {
        $this->conn = $conn;
        $this->finder = $finder ?? new Finder($conn);
    }

    private function normalizeRemovePath(string $path): string
    {
        $path = rtrim($path, '/');
        if (str_ends_with($path, '/print')) {
            $path = substr($path, 0, -6);
        }
        if (!str_ends_with($path, '/remove')) {
            $path .= '/remove';
        }
        return $path;
    }

    /**
     * Remove an item by name or .id.
     *
     * @param string $path Target path e.g. '/ip/hotspot/user'
     * @param string $idOrName Internal .id or item name
     * @return array
     */
    public function remove(string $path, string $idOrName): array
    {
        $id = str_starts_with($idOrName, '*')
            ? $idOrName
            : ($this->finder->nameToId($idOrName, $path) ?? $idOrName);

        $removePath = $this->normalizeRemovePath($path);
        return $this->conn->remove($removePath, $id);
    }

    /**
     * Remove an item specifically by its name.
     *
     * @param string $name
     * @param string $path
     * @return array
     */
    public function byName(string $name, string $path = '/ip/hotspot/user'): array
    {
        return $this->remove($path, $name);
    }

    /**
     * Remove an item specifically by internal .id.
     *
     * @param string $id
     * @param string $path
     * @return array
     */
    public function byId(string $id, string $path = '/ip/hotspot/user'): array
    {
        $removePath = $this->normalizeRemovePath($path);
        return $this->conn->remove($removePath, $id);
    }

    /**
     * Remove multiple items in high-speed batches using comma-separated IDs.
     * Dramatically reduces socket overhead when purging large numbers of records.
     *
     * @param string $path
     * @param array $idsOrNames
     * @param int $chunkSize
     * @return array
     */
    public function batch(string $path, array $idsOrNames, int $chunkSize = 50): array
    {
        $removePath = $this->normalizeRemovePath($path);
        $totalRemoved = 0;
        $errors = [];

        $ids = [];
        foreach ($idsOrNames as $item) {
            if (empty($item)) continue;
            $id = str_starts_with($item, '*') ? $item : ($this->finder->nameToId($item, $path) ?? $item);
            if (!empty($id)) {
                $ids[] = $id;
            }
        }

        if (empty($ids)) {
            return ['requested' => count($idsOrNames), 'removed' => 0, 'errors' => []];
        }

        foreach (array_chunk($ids, max(1, $chunkSize)) as $chunk) {
            $batchParam = implode(',', $chunk);
            $res = $this->conn->remove($removePath, $batchParam);
            if (isset($res['error'])) {
                $errors[] = ['chunk' => $chunk, 'error' => $res['error']];
            } else {
                $totalRemoved += count($chunk);
            }
        }

        return [
            'requested' => count($idsOrNames),
            'removed'   => $totalRemoved,
            'errors'    => $errors,
        ];
    }

    /**
     * Remove all items matching criteria.
     * Optimized to purge in batches rather than individual roundtrips.
     *
     * @param string $path
     * @param array $criteria
     * @return array ['total_found' => int, 'removed' => int, 'errors' => array]
     */
    public function where(string $path, array $criteria): array
    {
        $items = $this->finder->where($path, $criteria);
        $ids = array_filter(array_column($items, '.id'));

        if (empty($ids)) {
            return [
                'total_found' => 0,
                'removed'     => 0,
                'errors'      => [],
            ];
        }

        $batchRes = $this->batch($path, $ids);

        return [
            'total_found' => count($items),
            'removed'     => $batchRes['removed'],
            'errors'      => $batchRes['errors'],
        ];
    }

    /**
     * Remove items by comment tag.
     *
     * @param string $comment
     * @param string $path
     * @return array
     */
    public function byComment(string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->where($path, ['comment' => $comment]);
    }

    /**
     * Remove items by profile name.
     *
     * @param string $profile
     * @param string $path
     * @return array
     */
    public function byProfile(string $profile, string $path = '/ip/hotspot/user'): array
    {
        return $this->where($path, ['profile' => $profile]);
    }

    /**
     * Remove all items at an endpoint, with optional exclusion list.
     *
     * @param string $path
     * @param array $exceptNames Names to protect from deletion (e.g. ['default', 'admin'])
     * @return array
     */
    public function all(string $path, array $exceptNames = ['default', 'admin']): array
    {
        $items = $this->finder->where($path, []);
        $removed = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $name = $item['name'] ?? '';
            $id = $item['.id'] ?? null;

            if (in_array($name, $exceptNames, true)) {
                $skipped++;
                continue;
            }

            if ($id) {
                $this->byId($id, $path);
                $removed++;
            }
        }

        return [
            'total'   => count($items),
            'removed' => $removed,
            'skipped' => $skipped,
        ];
    }
}
