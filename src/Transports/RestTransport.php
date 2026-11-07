<?php

namespace RouterOSFramework\Transports;

use Throwable;

/**
 * Class RestTransport
 * Communicates with MikroTik RouterOS v7+ Native REST HTTP/HTTPS API.
 * Default ports: 80 (HTTP) / 443 (HTTPS).
 * Requires RouterOS v7.1 or newer with 'www' or 'www-ssl' service enabled.
 */
class RestTransport implements TransportInterface
{
    private array $config;
    private ?string $lastError = null;
    private bool $connected = false;

    public function __construct(array $config)
    {
        $port = isset($config['port']) && in_array((int)$config['port'], [80, 443, 8080, 8443])
            ? (int)$config['port']
            : (!empty($config['ssl']) ? 443 : 80);

        $this->config = [
            'host'    => $config['host'] ?? '192.168.88.1',
            'user'    => $config['user'] ?? ($config['username'] ?? 'admin'),
            'pass'    => $config['pass'] ?? ($config['password'] ?? ''),
            'port'    => $port,
            'timeout' => (int)($config['timeout'] ?? 3),
            'ssl'     => (bool)($config['ssl'] ?? ($port === 443 || $port === 8443)),
            'verify_ssl' => (bool)($config['verify_ssl'] ?? false),
        ];
    }

    private function getBaseUrl(): string
    {
        $scheme = $this->config['ssl'] ? 'https' : 'http';
        return "{$scheme}://{$this->config['host']}:{$this->config['port']}/rest";
    }

    /**
     * Normalize RouterOS path for REST endpoint.
     * e.g. '/ip/hotspot/user/print' -> 'ip/hotspot/user'
     * e.g. '/ip/hotspot/user/add'   -> 'ip/hotspot/user'
     */
    private function normalizeRestPath(string $path): string
    {
        $path = trim($path, '/');
        foreach (['/print', '/add', '/set', '/remove', '/enable', '/disable'] as $suffix) {
            if (str_ends_with($path, trim($suffix, '/'))) {
                $path = substr($path, 0, -strlen(trim($suffix, '/')));
                $path = rtrim($path, '/');
                break;
            }
        }
        return $path;
    }

    private function request(string $method, string $path, array $data = [], array $queryParams = []): array
    {
        $url = $this->getBaseUrl() . '/' . ltrim($path, '/');
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        if (function_exists('curl_init')) {
            return $this->requestCurl($method, $url, $data);
        }

        return $this->requestStream($method, $url, $data);
    }

    private function requestCurl(string $method, string $url, array $data = []): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['user'] . ':' . $this->config['pass']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!$this->config['verify_ssl']) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $json = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            $headers[] = 'Content-Length: ' . strlen($json);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            @curl_close($ch);
        }

        if ($response === false || $error) {
            $this->lastError = "REST cURL error: " . ($error ?: 'Connection failed');
            return ['error' => $this->lastError];
        }

        $this->lastError = null;
        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = is_array($decoded) ? ($decoded['detail'] ?? ($decoded['message'] ?? $response)) : "HTTP Error {$httpCode}";
            $this->lastError = "REST [{$httpCode}]: {$msg}";
            return ['error' => $this->lastError, 'http_code' => $httpCode];
        }

        return is_array($decoded) ? $decoded : ['raw' => $response, 'http_code' => $httpCode];
    }

    private function requestStream(string $method, string $url, array $data = []): array
    {
        try {
            $auth = base64_encode($this->config['user'] . ':' . $this->config['pass']);
            $opts = [
                'http' => [
                    'method'  => $method,
                    'header'  => "Authorization: Basic {$auth}\r\n" .
                                 "Accept: application/json\r\n" .
                                 "Content-Type: application/json\r\n",
                    'timeout' => $this->config['timeout'],
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => $this->config['verify_ssl'],
                    'verify_peer_name' => $this->config['verify_ssl'],
                ]
            ];

            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $opts['http']['content'] = json_encode($data);
            }

            $context = stream_context_create($opts);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                $this->lastError = "REST Stream connection failed to {$url}";
                return ['error' => $this->lastError];
            }

            $this->lastError = null;
            $decoded = json_decode($response, true);
            return is_array($decoded) ? $decoded : ['raw' => $response];
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            return ['error' => $e->getMessage()];
        }
    }

    public function connect(): bool
    {
        // Test connectivity via /rest/system/resource
        $res = $this->request('GET', 'system/resource');
        if (isset($res['error'])) {
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        $this->lastError = null;
        return true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        if ($this->connected) {
            return true;
        }
        return $this->connect();
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function read(string $endpoint, array $where = []): array
    {
        $path = $this->normalizeRestPath($endpoint);
        $res = $this->request('GET', $path, [], $where);
        return is_array($res) ? $res : [];
    }

    public function write(string $endpoint, array $params = []): array
    {
        $path = $this->normalizeRestPath($endpoint);
        return $this->request('PUT', $path, $params);
    }

    public function set(string $endpoint, string $id, array $params): array
    {
        $path = $this->normalizeRestPath($endpoint) . '/' . urlencode($id);
        return $this->request('PATCH', $path, $params);
    }

    public function remove(string $endpoint, string $id): array
    {
        $path = $this->normalizeRestPath($endpoint) . '/' . urlencode($id);
        return $this->request('DELETE', $path);
    }

    public function execute(string $command, array $params = []): array
    {
        $path = trim($command, '/');
        return $this->request('POST', $path, $params);
    }

    public function execRaw(string $cliCommand): string
    {
        // RouterOS v7 rest script execution endpoint or print
        $cleanCmd = trim($cliCommand, '/');
        $res = $this->request('GET', $cleanCmd);
        return json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getProtocol(): string
    {
        return 'rest';
    }
}
