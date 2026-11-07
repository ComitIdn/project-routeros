<?php

namespace RouterOSFramework\Core;

/**
 * Class RouterSession
 * Structured, isolated session manager per MikroTik RouterOS device.
 * Stores runtime tokens, authentication state, temporary variables, and health metrics
 * in isolated router-specific storage (database/sessions/router_{id}.json).
 */
class RouterSession
{
    private string $routerId;
    private string $sessionDir;
    private string $filePath;
    private array $data = [];
    private bool $loaded = false;

    public function __construct(string $routerId, ?string $sessionDir = null)
    {
        $this->routerId = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $routerId) ?: 'default';
        $this->sessionDir = $sessionDir ?? (__DIR__ . '/../../database/sessions');

        if (!is_dir($this->sessionDir)) {
            mkdir($this->sessionDir, 0755, true);
        }

        $this->filePath = $this->sessionDir . "/router_{$this->routerId}.json";
        $this->load();
    }

    /**
     * Load session data from disk.
     */
    private function load(): void
    {
        if (file_exists($this->filePath)) {
            $content = @file_get_contents($this->filePath);
            $decoded = $content ? json_decode($content, true) : null;
            $this->data = is_array($decoded) ? $decoded : [];
        } else {
            $this->data = [
                'session_id'   => bin2hex(random_bytes(16)),
                'router_id'    => $this->routerId,
                'created_at'   => date('Y-m-d H:i:s'),
                'last_active'  => time(),
                'attributes'   => [],
            ];
            $this->save();
        }
        $this->loaded = true;
    }

    /**
     * Save session state to disk with atomic lock.
     */
    public function save(): bool
    {
        $this->data['last_active'] = time();
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return @file_put_contents($this->filePath, $json, LOCK_EX) !== false;
    }

    public function getRouterId(): string
    {
        return $this->routerId;
    }

    public function getSessionId(): string
    {
        return $this->data['session_id'] ?? '';
    }

    public function getToken(): string
    {
        return (string)($this->data['token'] ?? $this->data['session_id'] ?? '');
    }

    public function setToken(string $token): self
    {
        $this->data['token'] = $token;
        $this->save();
        return $this;
    }

    /**
     * Get a session attribute.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data['attributes'][$key] ?? $default;
    }

    /**
     * Set a session attribute.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->data['attributes'][$key] = $value;
        $this->save();
        return $this;
    }

    /**
     * Check if a session attribute exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data['attributes'] ?? []);
    }

    /**
     * Remove a session attribute.
     *
     * @param string $key
     * @return self
     */
    public function remove(string $key): self
    {
        unset($this->data['attributes'][$key]);
        $this->save();
        return $this;
    }

    /**
     * Get all attributes for this router session.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->data['attributes'] ?? [];
    }

    /**
     * Touch session activity timestamp.
     */
    public function touch(): void
    {
        $this->data['last_active'] = time();
        $this->save();
    }

    /**
     * Check if session is still within active timeout.
     *
     * @param int $maxIdleSeconds (default: 3600s / 1 hour)
     * @return bool
     */
    public function isValid(int $maxIdleSeconds = 3600): bool
    {
        $lastActive = (int)($this->data['last_active'] ?? 0);
        return (time() - $lastActive) <= $maxIdleSeconds;
    }

    /**
     * Clear all attributes in this router session.
     */
    public function clear(): self
    {
        $this->data['attributes'] = [];
        $this->save();
        return $this;
    }

    /**
     * Destroy and remove session file from disk.
     */
    public function destroy(): bool
    {
        $this->data = [];
        if (file_exists($this->filePath)) {
            return @unlink($this->filePath);
        }
        return true;
    }
}
