<?php

namespace RouterOSFramework\Transports;

use Throwable;

/**
 * Class SshTransport
 * Communicates with MikroTik RouterOS CLI via SSH (Default port 22).
 * Supports PHP ext-ssh2 or system SSH client fallback, with automatic output parser.
 */
class SshTransport implements TransportInterface
{
    private array $config;
    private ?string $lastError = null;
    private $sshConnection = null;
    private bool $connected = false;

    public function __construct(array $config)
    {
        $this->config = [
            'host'    => $config['host'] ?? '192.168.88.1',
            'user'    => $config['user'] ?? ($config['username'] ?? 'admin'),
            'pass'    => $config['pass'] ?? ($config['password'] ?? ''),
            'port'    => (int)($config['port'] ?? 22),
            'timeout' => (int)($config['timeout'] ?? 5),
            'key'     => $config['key'] ?? null,
        ];
    }

    public function connect(): bool
    {
        // Check if ssh2 extension is loaded
        if (function_exists('ssh2_connect')) {
            try {
                $conn = @ssh2_connect($this->config['host'], $this->config['port']);
                if ($conn) {
                    $auth = false;
                    if (!empty($this->config['key']) && file_exists($this->config['key'])) {
                        $auth = @ssh2_auth_pubkey_file($conn, $this->config['user'], $this->config['key'] . '.pub', $this->config['key'], $this->config['pass']);
                    } else {
                        $auth = @ssh2_auth_password($conn, $this->config['user'], $this->config['pass']);
                    }

                    if ($auth) {
                        $this->sshConnection = $conn;
                        $this->connected = true;
                        $this->lastError = null;
                        return true;
                    }
                }
            } catch (Throwable $e) {
                // fallback to CLI
            }
        }

        // Test connectivity via raw socket
        $fp = @fsockopen($this->config['host'], $this->config['port'], $errno, $errstr, $this->config['timeout']);
        if ($fp) {
            fclose($fp);
            $this->connected = true;
            $this->lastError = null;
            return true;
        }

        $this->connected = false;
        $this->lastError = "SSH Connection failed to {$this->config['host']}:{$this->config['port']}";
        return false;
    }

    public function disconnect(): void
    {
        if (function_exists('ssh2_disconnect') && $this->sshConnection) {
            @ssh2_disconnect($this->sshConnection);
        }
        $this->sshConnection = null;
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function execRaw(string $cliCommand): string
    {
        // 1. Try PHP ssh2 extension
        if ($this->sshConnection && function_exists('ssh2_exec')) {
            $stream = @ssh2_exec($this->sshConnection, $cliCommand);
            if ($stream) {
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                fclose($stream);
                return (string)$output;
            }
        }

        // 2. Try CLI ssh command if available
        $cmdEscaped = escapeshellarg($cliCommand);
        $user = escapeshellarg($this->config['user']);
        $host = escapeshellarg($this->config['host']);
        $port = (int)$this->config['port'];

        // If sshpass is installed or password in command
        $sshCmd = "ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout={$this->config['timeout']} -p {$port} {$user}@{$host} {$cmdEscaped}";
        if (!empty($this->config['pass']) && shell_exec('which sshpass 2>/dev/null')) {
            $passEscaped = escapeshellarg($this->config['pass']);
            $sshCmd = "sshpass -p {$passEscaped} " . $sshCmd;
        }

        $output = @shell_exec($sshCmd . ' 2>&1');
        return $output !== null ? (string)$output : '';
    }

    /**
     * Parse RouterOS CLI print detail text output into structured array.
     */
    public function parseCliOutput(string $output): array
    {
        $output = trim($output);
        if (empty($output)) {
            return [];
        }

        $lines = explode("\n", $output);
        $records = [];
        $current = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, 'Flags:')) {
                continue;
            }

            // Line starting with number e.g. "0 name=admin address=..." or " 0 R  name=wlan1"
            if (preg_match('/^\d+\s+/', $line) || preg_match('/^\*\w+/', $line)) {
                if (!empty($current)) {
                    $records[] = $current;
                    $current = [];
                }
            }

            // Extract key=value or key: value pairs
            preg_match_all('/(?:([a-zA-Z0-9_\-\.]+)=([^\s"]+|"[^"]*")|([a-zA-Z0-9_\-\.]+):\s*([^\s]+))/', $line, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if (!empty($match[1])) {
                    $key = $match[1];
                    $val = trim($match[2], '"');
                } elseif (!empty($match[3])) {
                    $key = $match[3];
                    $val = trim($match[4], '"');
                } else {
                    continue;
                }
                $current[$key] = $val;
            }
        }

        if (!empty($current)) {
            $records[] = $current;
        }

        return $records;
    }

    public function read(string $endpoint, array $where = []): array
    {
        $cmd = trim($endpoint);
        if (!str_ends_with($cmd, 'print') && !str_ends_with($cmd, '/print')) {
            $cmd = rtrim($cmd, '/') . ' print';
        }

        $cmd = str_replace('/', ' ', ltrim($cmd, '/'));
        $cmd .= ' detail';

        if (!empty($where)) {
            $cmd .= ' where';
            foreach ($where as $k => $v) {
                $cmd .= " {$k}=\"{$v}\"";
            }
        }

        $raw = $this->execRaw($cmd);
        $parsed = $this->parseCliOutput($raw);
        return !empty($parsed) ? $parsed : [['raw' => $raw]];
    }

    public function write(string $endpoint, array $params = []): array
    {
        $cmd = str_replace('/', ' ', ltrim($endpoint, '/'));
        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $val = is_bool($v) ? ($v ? 'yes' : 'no') : (string)$v;
                $cmd .= " {$k}=\"{$val}\"";
            }
        }

        $raw = $this->execRaw($cmd);
        return ['status' => 'executed', 'output' => trim($raw)];
    }

    public function set(string $endpoint, string $id, array $params): array
    {
        $cmd = str_replace('/', ' ', ltrim($endpoint, '/'));
        $cmd .= " \"{$id}\"";
        foreach ($params as $k => $v) {
            if ($v !== null && $v !== '') {
                $val = is_bool($v) ? ($v ? 'yes' : 'no') : (string)$v;
                $cmd .= " {$k}=\"{$val}\"";
            }
        }

        $raw = $this->execRaw($cmd);
        return ['status' => 'set', 'output' => trim($raw)];
    }

    public function remove(string $endpoint, string $id): array
    {
        $cmd = str_replace('/', ' ', ltrim($endpoint, '/'));
        $cmd .= " \"{$id}\"";
        $raw = $this->execRaw($cmd);
        return ['status' => 'removed', 'output' => trim($raw)];
    }

    public function execute(string $command, array $params = []): array
    {
        return $this->write($command, $params);
    }

    public function getProtocol(): string
    {
        return 'ssh';
    }
}
