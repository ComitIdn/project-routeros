<?php

namespace RouterOSFramework\Actions;

use RouterOSFramework\Core\Connection;
use RouterOS\Query;

/**
 * Class Printer
 * Dedicated operational class for executing /print commands with formatting, projection, and pagination.
 * Supports RouterOS v6.xx and v7.xx.
 */
class Printer
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
     * Execute a /print query with where filters and optional property list.
     *
     * @param string $path
     * @param array $where
     * @param array $proplist List of properties to fetch (e.g. ['.id', 'name', 'profile'])
     * @return array
     */
    public function print(string $path, array $where = [], array $proplist = []): array
    {
        $printPath = $this->normalizePrintPath($path);
        $query = new Query($printPath);

        foreach ($where as $k => $v) {
            $query->where($k, (string)$v);
        }

        if (!empty($proplist)) {
            $query->operations(['.proplist' => implode(',', $proplist)]);
        }

        $res = $this->conn->executeQuery($query);
        return (!empty($res) && is_array($res) && !isset($res['error'])) ? $res : [];
    }

    /**
     * Retrieve specific columns only.
     *
     * @param string $path
     * @param array $columns
     * @param array $where
     * @return array
     */
    public function printColumns(string $path, array $columns, array $where = []): array
    {
        return $this->print($path, $where, $columns);
    }

    /**
     * Print full details of a single item by its ID or name.
     *
     * @param string $path
     * @param string $idOrName
     * @return array|null
     */
    public function printDetail(string $path, string $idOrName): ?array
    {
        if (str_starts_with($idOrName, '*')) {
            return $this->finder->byId($idOrName, $path);
        }
        return $this->finder->byName($idOrName, $path);
    }

    /**
     * Print formatted JSON string.
     *
     * @param string $path
     * @param array $where
     * @return string
     */
    public function printJson(string $path, array $where = []): string
    {
        return json_encode($this->print($path, $where), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Paginate results from an endpoint.
     *
     * @param string $path
     * @param int $page
     * @param int $perPage
     * @param array $where
     * @return array
     */
    public function paginate(string $path, int $page = 1, int $perPage = 20, array $where = []): array
    {
        $items = $this->print($path, $where);
        $total = count($items);
        $offset = max(0, ($page - 1) * $perPage);
        $sliced = array_slice($items, $offset, $perPage);

        return [
            'data'         => $sliced,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => (int)ceil($total / max(1, $perPage)),
        ];
    }
}
