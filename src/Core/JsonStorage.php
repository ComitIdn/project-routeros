<?php

namespace RouterOSFramework\Core;

/**
 * Class JsonStorage
 * Ultra-fast JSON flat-file storage engine for lightweight web apps, microservices, and bots.
 */
class JsonStorage
{
    private string $storageDir;
    private array $memoryCache = [];

    /**
     * JsonStorage constructor.
     *
     * @param string|null $storageDir Directory where JSON files will be stored.
     */
    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? (__DIR__ . '/../../database/json');
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Get full path to JSON file.
     *
     * @param string $file File name with or without .json
     * @return string
     */
    private function resolvePath(string $file): string
    {
        if (!str_ends_with(strtolower($file), '.json')) {
            $file .= '.json';
        }
        return $this->storageDir . '/' . ltrim($file, '/');
    }

    /**
     * Read all data from a JSON file (with internal request caching).
     *
     * @param string $file
     * @param bool $forceReload
     * @return array
     */
    public function all(string $file, bool $forceReload = false): array
    {
        if (!$forceReload && isset($this->memoryCache[$file])) {
            return $this->memoryCache[$file];
        }

        $path = $this->resolvePath($file);
        if (!file_exists($path)) {
            $this->memoryCache[$file] = [];
            return [];
        }

        $content = file_get_contents($path);
        if (!$content) {
            $this->memoryCache[$file] = [];
            return [];
        }

        $decoded = json_decode($content, true);
        $result = is_array($decoded) ? $decoded : [];
        $this->memoryCache[$file] = $result;
        return $result;
    }

    /**
     * Save full array data to a JSON file atomically with LOCK_EX.
     *
     * @param string $file
     * @param array $data
     * @return bool
     */
    public function saveAll(string $file, array $data): bool
    {
        $path = $this->resolvePath($file);
        $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $res = file_put_contents($path, $encoded, LOCK_EX) !== false;
        if ($res) {
            $this->memoryCache[$file] = $data;
        }
        return $res;
    }

    /**
     * Get a specific key or entire dataset.
     *
     * @param string $file
     * @param string|null $key Dot notation supported (e.g. 'users.0.name')
     * @param mixed $default
     * @return mixed
     */
    public function get(string $file, ?string $key = null, $default = null)
    {
        $data = $this->all($file);
        if ($key === null || $key === '') {
            return $data;
        }

        $keys = explode('.', $key);
        foreach ($keys as $k) {
            if (is_array($data) && array_key_exists($k, $data)) {
                $data = $data[$k];
            } else {
                return $default;
            }
        }
        return $data;
    }

    /**
     * Set a value at specific key.
     *
     * @param string $file
     * @param string $key Dot notation supported
     * @param mixed $value
     * @return bool
     */
    public function set(string $file, string $key, $value): bool
    {
        $data = $this->all($file);
        $keys = explode('.', $key);
        $current = &$data;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
        return $this->saveAll($file, $data);
    }

    /**
     * Append a record to a list collection.
     *
     * @param string $file
     * @param array $item
     * @return bool
     */
    public function push(string $file, array $item): bool
    {
        $data = $this->all($file);
        if (!isset($item['id'])) {
            $item['id'] = count($data) > 0 ? (max(array_column($data, 'id') ?: [0]) + 1) : 1;
        }
        if (!isset($item['created_at'])) {
            $item['created_at'] = date('Y-m-d H:i:s');
        }
        $data[] = $item;
        return $this->saveAll($file, $data);
    }

    /**
     * Append multiple records at once with a single disk write.
     * Crucial for high-speed bulk voucher and user generation without I/O thrashing.
     *
     * @param string $file
     * @param array $items
     * @return bool
     */
    public function pushBatch(string $file, array $items): bool
    {
        if (empty($items)) {
            return true;
        }

        $data = $this->all($file);
        $nextId = count($data) > 0 ? (max(array_column($data, 'id') ?: [0]) + 1) : 1;
        $now = date('Y-m-d H:i:s');

        foreach ($items as &$item) {
            if (!isset($item['id'])) {
                $item['id'] = $nextId++;
            }
            if (!isset($item['created_at'])) {
                $item['created_at'] = $now;
            }
            $data[] = $item;
        }

        return $this->saveAll($file, $data);
    }

    /**
     * Find single record matching field condition.
     *
     * @param string $file
     * @param string $field
     * @param mixed $value
     * @return array|null
     */
    public function find(string $file, string $field, $value): ?array
    {
        $data = $this->all($file);
        foreach ($data as $item) {
            if (isset($item[$field]) && $item[$field] == $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Filter records matching field condition.
     *
     * @param string $file
     * @param string $field
     * @param mixed $value
     * @return array
     */
    public function where(string $file, string $field, $value): array
    {
        $data = $this->all($file);
        $results = [];
        foreach ($data as $item) {
            if (isset($item[$field]) && $item[$field] == $value) {
                $results[] = $item;
            }
        }
        return $results;
    }

    /**
     * Delete records matching field condition.
     *
     * @param string $file
     * @param string $field
     * @param mixed $value
     * @return int Number of deleted records
     */
    public function deleteWhere(string $file, string $field, $value): int
    {
        $data = $this->all($file);
        $initial = count($data);
        $data = array_values(array_filter($data, function ($item) use ($field, $value) {
            return !(isset($item[$field]) && $item[$field] == $value);
        }));

        $deleted = $initial - count($data);
        if ($deleted > 0) {
            $this->saveAll($file, $data);
        }
        return $deleted;
    }

    /**
     * Delete an entire file or clear its contents.
     *
     * @param string $file
     * @return bool
     */
    public function clear(string $file): bool
    {
        return $this->saveAll($file, []);
    }

    /**
     * Get storage directory path.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->storageDir;
    }
}
