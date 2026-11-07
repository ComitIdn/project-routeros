<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;

/**
 * Class Getter
 * Dedicated operational class for retrieving, counting, and extracting RouterOS data.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Getter
{
    private Connection $conn;
    private Finder $finder;

    public function __construct(Connection $conn, ?Finder $finder = null)
    {
        $this->conn = $conn;
        $this->finder = $finder ?? new Finder($conn);
    }

    private function normalizePrintPath(string $path): string
    {
        $path = rtrim($path, '/');
        if (!str_ends_with($path, '/print')) {
            $path .= '/print';
        }
        return $path;
    }

    /**
     * Retrieve all items matching optional criteria from an endpoint.
     *
     * @param string $path
     * @param array $where
     * @return array
     */
    public function get(string $path, array $where = []): array
    {
        $printPath = $this->normalizePrintPath($path);
        $res = $this->conn->read($printPath, $where);
        return (!empty($res) && is_array($res) && !isset($res['error'])) ? $res : [];
    }

    /**
     * Get a single item by its 'name' property.
     *
     * @param string $name
     * @param string $path
     * @return array|null
     */
    public function getByName(string $name, string $path = '/ip/hotspot/user'): ?array
    {
        return $this->finder->byName($name, $path);
    }

    /**
     * Get a single item by its internal '.id'.
     *
     * @param string $id
     * @param string $path
     * @return array|null
     */
    public function getById(string $id, string $path = '/ip/hotspot/user'): ?array
    {
        return $this->finder->byId($id, $path);
    }

    /**
     * Retrieve the first item matching criteria.
     *
     * @param string $path
     * @param array $where
     * @return array|null
     */
    public function first(string $path, array $where = []): ?array
    {
        $items = $this->get($path, $where);
        return $items[0] ?? null;
    }

    /**
     * Get a single specific property value of an item.
     *
     * @param string $path
     * @param string $idOrName
     * @param string $property
     * @return mixed|null
     */
    public function getValue(string $path, string $idOrName, string $property)
    {
        $item = str_starts_with($idOrName, '*')
            ? $this->getById($idOrName, $path)
            : $this->getByName($idOrName, $path);

        return $item[$property] ?? null;
    }

    /**
     * Count items in an endpoint matching optional criteria.
     *
     * @param string $path
     * @param array $where
     * @return int
     */
    public function count(string $path, array $where = []): int
    {
        return count($this->get($path, $where));
    }

    /**
     * Check if any item exists matching criteria.
     *
     * @param string $path
     * @param array $where
     * @return bool
     */
    public function exists(string $path, array $where = []): bool
    {
        return $this->count($path, $where) > 0;
    }

    /**
     * Pluck a single column from all matching records.
     *
     * @param string $path
     * @param string $column
     * @param array $where
     * @return array
     */
    public function pluck(string $path, string $column, array $where = []): array
    {
        $items = $this->get($path, $where);
        $result = [];
        foreach ($items as $item) {
            if (isset($item[$column])) {
                $result[] = $item[$column];
            }
        }
        return $result;
    }
}
