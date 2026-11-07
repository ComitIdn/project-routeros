<?php

namespace RouterOSFramework\Transports;

use Throwable;

/**
 * Class TelnetTransport
 * Communicates with MikroTik RouterOS via raw Telnet protocol (Default port 23).
 * Zero external dependencies - uses standard PHP network sockets.
 */
class TelnetTransport implements TransportInterface
{
    private array $config;
    private $socket = null;
    private ?string $lastError = null;
    private bool $connected = false;

    public function __construct(array $config)
    {
        $this->config = [
            'host'    => $config['host'] ?? '192.168.88.1',
            'user'    => $config['user'] ?? ($config['username'] ?? 'admin'),
            'pass'    => $config['pass'] ?? ($config['password'] ?? ''),
            'port'    => (int)($config['port'] ?? 23),
            'timeout' => (int)($config['timeout'] ?? 3),
        ];
    }

    public function connect(): bool
    {
        if ($this->connected && is_resource($this->socket)) {
            return true;
        }

        try {
            $errno = 0;
            $errstr = '';
            $this->socket = @fsockopen(
                $this->config['host'],
                $this->config['port'],
                $errno,
                $errstr,
                $this->config['timeout']
            );

            if (!$this->socket) {
                $this->lastError = "Telnet connect failed: {$errstr} ({$errno})";
                $this->connected = false;
                return false;
            }

            stream_set_timeout($this->socket, $this->config['timeout']);

            // Read until Login:
            $banner = $this->readUntil(['Login:', 'login:']);
            // MikroTik username: append '+ct' for terminal mode
            $this->writeRaw($this->config['user'] . "+ct\r\n");

            // Read until Password:
            $this->readUntil(['Password:', 'password:']);
            $this->writeRaw($this->config['pass'] . "\r\n");

            // Read prompt e.g. [admin@MikroTik] >
            $prompt = $this->readUntil(['>', '#']);

            $this->connected = true;
            $this->lastError = null;
            return true;
        } catch (Throwable $e) {
            $this->lastError = "Telnet exception: " . $e->getMessage();
            $this->disconnect();
            return false;
        }
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected && is_resource($this->socket);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function writeRaw(string $data): void
    {
        if (!is_resource($this->socket)) {
            return;
        }
        @fwrite($this->socket, $data);
    }

    private function readUntil(array $prompts, int $timeoutSeconds = 3): string
    {
        $buffer = '';
        $start = time();

        while (is_resource($this->socket) && (time() - $start < $timeoutSeconds)) {
            $char = @fgetc($this->socket);
            if ($char === false) {
                break;
            }

            // Telnet IAC negotiation handling (RFC 854)
            if ($char === "\xFF") {
                $cmd = @fgetc($this->socket);
                $opt = @fgetc($this->socket);
                if (in_array(ord($cmd), [251, 252])) { // WILL / WONT -> reply DONT
                    @fwrite($this->socket, "\xFF\xFE" . $opt);
                } elseif (in_array(ord($cmd), [253, 254])) { // DO / DONT -> reply WONT
                    @fwrite($this->socket, "\xFF\xFC" . $opt);
                }
                continue;
            }

            $buffer .= $char;

            foreach ($prompts as $p) {
                if (str_ends_with(rtrim($buffer), $p)) {
                    return $buffer;
                }
            }
        }

        return $buffer;
    }

    private function stripAnsi(string $str): string
    {
        return preg_replace('/\x1b(\[[0-9;]*[a-zA-Z]|\([a-zA-Z])/', '', $str);
    }

    public function execRaw(string $cliCommand): string
    {
        if (!$this->isConnected() && !$this->connect()) {
            return '';
        }

        $cmd = trim($cliCommand) . "\r\n";
        $this->writeRaw($cmd);
        $output = $this->readUntil(['>', '#'], $this->config['timeout']);

        $clean = $this->stripAnsi($output);
        // Remove echoed command and prompt
        $lines = explode("\n", $clean);
        if (count($lines) > 2) {
            array_shift($lines); // Command
            array_pop($lines);   // Prompt
        }

        return trim(implode("\n", $lines));
    }

    public function read(string $endpoint, array $where = []): array
    {
        $cmd = trim($endpoint);
        if (!str_ends_with($cmd, 'print') && !str_ends_with($cmd, '/print')) {
            $cmd = rtrim($cmd, '/') . ' print';
        }

        $cmd = str_replace('/', ' ', ltrim($cmd, '/')) . ' detail';
        if (!empty($where)) {
            $cmd .= ' where';
            foreach ($where as $k => $v) {
                $cmd .= " {$k}=\"{$v}\"";
            }
        }

        $raw = $this->execRaw($cmd);
        $sshHelper = new SshTransport($this->config);
        $parsed = $sshHelper->parseCliOutput($raw);
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
        $cmd = str_replace('/', ' ', ltrim($endpoint, '/')) . " \"{$id}\"";
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
        $cmd = str_replace('/', ' ', ltrim($endpoint, '/')) . " \"{$id}\"";
        $raw = $this->execRaw($cmd);
        return ['status' => 'removed', 'output' => trim($raw)];
    }

    public function execute(string $command, array $params = []): array
    {
        return $this->write($command, $params);
    }

    public function getProtocol(): string
    {
        return 'telnet';
    }
}
