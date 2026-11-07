<?php

namespace RouterOSFramework\Core;

/**
 * Class Cache
 * High-performance TTL file & memory caching layer to protect MikroTik from API overload.
 */
class Cache
{
    private string $baseDir;
    private string $cacheDir;
    private ?string $routerId = null;
    private array $memoryCache = [];

    public function __construct(?string $cacheDir = null, ?string $routerId = null)
    {
        $this->baseDir = $cacheDir ?? (__DIR__ . '/../../database/cache');
        $this->routerId = $routerId ? preg_replace('/[^a-zA-Z0-9_\-]/', '_', $routerId) : null;
        $this->cacheDir = $this->routerId ? "{$this->baseDir}/{$this->routerId}" : $this->baseDir;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Create or return a cloned Cache instance scoped to a specific RouterOS device.
     * Prevents cache collisions when managing multiple routers simultaneously.
     *
     * @param string $routerId
     * @return self
     */
    public function forRouter(string $routerId): self
    {
        return new self($this->baseDir, $routerId);
    }

    public function getRouterId(): ?string
    {
        return $this->routerId;
    }

    private function getFilePath(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Get item from cache if still valid.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (isset($this->memoryCache[$key])) {
            $mem = $this->memoryCache[$key];
            if ($mem['expire'] > time()) {
                return $mem['val'];
            }
            unset($this->memoryCache[$key]);
        }

        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $content = @file_get_contents($file);
        if (!$content) {
            return $default;
        }

        $data = @unserialize($content);
        if (!is_array($data) || !isset($data['expire'], $data['val'])) {
            return $default;
        }

        if ($data['expire'] <= time()) {
            @unlink($file);
            return $default;
        }

        $this->memoryCache[$key] = $data;
        return $data['val'];
    }

    /**
     * Store item in cache with TTL in seconds.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttlSeconds (default 60 seconds)
     * @return bool
     */
    public function set(string $key, $value, int $ttlSeconds = 60): bool
    {
        $data = [
            'expire' => time() + $ttlSeconds,
            'val'    => $value,
        ];
        $this->memoryCache[$key] = $data;

        $file = $this->getFilePath($key);
        return (bool)@file_put_contents($file, serialize($data), LOCK_EX);
    }

    /**
     * Check if cache key exists and is not expired.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Forget / delete a cached key.
     *
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        unset($this->memoryCache[$key]);
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    /**
     * Clear cached items for this scoped cache directory.
     *
     * @return bool
     */
    public function clear(): bool
    {
        $this->memoryCache = [];
        $files = glob($this->cacheDir . '/*.cache');
        if ($files) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
        return true;
    }

    /**
     * Clear cache for a specific router by its ID.
     *
     * @param string $routerId
     * @return bool
     */
    public function clearRouter(string $routerId): bool
    {
        $cleanId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $routerId);
        $targetDir = "{$this->baseDir}/{$cleanId}";
        if (is_dir($targetDir)) {
            $files = glob($targetDir . '/*.cache');
            if ($files) {
                foreach ($files as $f) {
                    @unlink($f);
                }
            }
        }
        return true;
    }

    /**
     * Clear all caches across all routers.
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        $this->memoryCache = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->baseDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() === 'cache') {
                @unlink($item->getPathname());
            }
        }
        return true;
    }

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param string $key
     * @param int $ttlSeconds
     * @param callable $callback
     * @return mixed
     */
    public function remember(string $key, int $ttlSeconds, callable $callback)
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttlSeconds);
        return $value;
    }
}
