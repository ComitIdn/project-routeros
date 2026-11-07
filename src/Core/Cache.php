<?php

namespace RouterOSFramework\Core;

/**
 * Class Cache
 * High-performance TTL file & memory caching layer to protect MikroTik from API overload.
 */
class Cache
{
    private string $cacheDir;
    private array $memoryCache = [];

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? (__DIR__ . '/../../database/cache');
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
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
     * Clear all cached items.
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
