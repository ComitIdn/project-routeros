<?php

namespace RouterOSFramework\Core;

use RouterOSFramework\Modules\Backup;
use RouterOSFramework\Modules\Billing;
use RouterOSFramework\Modules\BotHandler;
use RouterOSFramework\Modules\DnsFilter;
use RouterOSFramework\Modules\Firewall;
use RouterOSFramework\Modules\Hotspot;
use RouterOSFramework\Modules\Ip;
use RouterOSFramework\Modules\NetworkInterface;
use RouterOSFramework\Modules\PPPoE;
use RouterOSFramework\Modules\Queue;
use RouterOSFramework\Modules\System;
use RouterOSFramework\Modules\Tool;
use RouterOSFramework\Modules\Voucher;
use RouterOSFramework\Modules\Vpn;

/**
 * Class RouterOSAPI
 * Full-featured RouterOS API client inspired by comitIdn/RouterOS-API,
 * extended with modular engines, SQL database, and JSON flat-file storage.
 */
class RouterOSAPI
{
    public bool $debug     = false;
    public bool $connected = false;
    public int $port       = 8728;
    public bool $ssl       = false;
    public int $timeout    = 3;
    public int $attempts   = 2;
    public int $delay      = 1;

    public $socket;
    public $error_no;
    public $error_str;

    private ?Database $db = null;
    private ?JsonStorage $jsonStorage = null;
    private ?Cache $cache = null;

    // Modules
    private ?Hotspot $hotspot = null;
    private ?Voucher $voucher = null;
    private ?PPPoE $pppoe = null;
    private ?Firewall $firewall = null;
    private ?Queue $queue = null;
    private ?NetworkInterface $interface = null;
    private ?System $system = null;
    private ?Ip $ipModule = null;
    private ?Backup $backup = null;
    private ?Tool $tool = null;
    private ?Vpn $vpn = null;
    private ?Billing $billing = null;
    private ?DnsFilter $dnsFilter = null;
    private ?BotHandler $bot = null;

    private ?Connection $internalConnection = null;
    private ?RouterOS $internalRos = null;
    private array $connectionParams = [];

    /**
     * RouterOSAPI constructor.
     *
     * @param array $config Optional configuration for auto-connect and database
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['database'])) {
            $this->db = new Database($config['database']);
        }
        $this->jsonStorage = new JsonStorage();
        $this->cache = new Cache();

        if (!empty($config['host'])) {
            $host = $config['host'];
            $user = $config['user'] ?? ($config['username'] ?? 'admin');
            $pass = $config['pass'] ?? ($config['password'] ?? '');
            $port = (int)($config['port'] ?? 8728);
            $this->connect($host, $user, $pass, $port);
        }
    }

    public function debug(string $text): void
    {
        if ($this->debug) {
            echo "[RouterOSAPI Debug] " . $text . "\n";
        }
    }

    public function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length >= 0x10000000) {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        return (string)$length;
    }

    public function connect($ip, $login, $password, $portdata = 8728): bool
    {
        $this->port = (int)($portdata ?: ($this->ssl ? 8729 : 8728));
        $this->connectionParams = [
            'host'    => $ip,
            'user'    => $login,
            'pass'    => $password,
            'port'    => $this->port,
            'timeout' => $this->timeout,
        ];

        for ($attempt = 1; $attempt <= $this->attempts; $attempt++) {
            $this->connected = false;
            $protocol = ($this->ssl ? 'ssl://' : '');
            $context = stream_context_create([
                'ssl' => [
                    'ciphers'          => 'ADH:ALL',
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ]
            ]);

            $this->debug("Connection attempt #{$attempt} to {$protocol}{$ip}:{$this->port}...");
            $this->socket = @stream_socket_client(
                "{$protocol}{$ip}:{$this->port}",
                $this->error_no,
                $this->error_str,
                $this->timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($this->socket) {
                socket_set_timeout($this->socket, $this->timeout);
                $this->write('/login', false);
                $this->write('=name=' . $login, false);
                $this->write('=password=' . $password);
                $response = $this->read(false);

                if (isset($response[0])) {
                    if ($response[0] == '!done') {
                        if (!isset($response[1])) {
                            $this->connected = true;
                            break;
                        } else {
                            $matches = [];
                            if (preg_match_all('/[^=]+/i', $response[1], $matches)) {
                                if ($matches[0][0] == 'ret' && strlen($matches[0][1]) == 32) {
                                    $this->write('/login', false);
                                    $this->write('=name=' . $login, false);
                                    $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $matches[0][1])));
                                    $response = $this->read(false);
                                    if (isset($response[0]) && $response[0] == '!done') {
                                        $this->connected = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                @fclose($this->socket);
            }
            if ($attempt < $this->attempts) {
                sleep($this->delay);
            }
        }

        if ($this->connected) {
            $this->debug('Connected successfully.');
            $this->internalConnection = new Connection($this->connectionParams, false);
            $this->internalRos = new RouterOS($this->connectionParams);
            if ($this->db) {
                $this->internalRos->withDatabase($this->db);
            }
        } else {
            $this->debug('Connection failed.');
        }

        return $this->connected;
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->connected = false;
        $this->debug('Disconnected.');
    }

    public function parseResponse(array $response): array
    {
        $parsed = [];
        $current = null;
        $singleValue = null;

        foreach ($response as $x) {
            if (in_array($x, ['!fatal', '!re', '!trap'])) {
                if ($x == '!re') {
                    $current = &$parsed[];
                } else {
                    $current = &$parsed[$x][];
                }
            } elseif ($x != '!done') {
                $matches = [];
                if (preg_match_all('/[^=]+/i', $x, $matches)) {
                    if ($matches[0][0] == 'ret') {
                        $singleValue = $matches[0][1];
                    }
                    $current[$matches[0][0]] = $matches[0][1] ?? '';
                }
            }
        }

        if (empty($parsed) && !is_null($singleValue)) {
            $parsed = $singleValue;
        }

        return $parsed;
    }

    public function read(bool $parse = true): array
    {
        $response = [];
        $receivedDone = false;

        while (is_resource($this->socket) && !feof($this->socket)) {
            $byte = ord(fread($this->socket, 1));
            $length = 0;

            if ($byte & 128) {
                if (($byte & 192) == 128) {
                    $length = (($byte & 63) << 8) + ord(fread($this->socket, 1));
                } elseif (($byte & 224) == 192) {
                    $length = (($byte & 31) << 8) + ord(fread($this->socket, 1));
                    $length = ($length << 8) + ord(fread($this->socket, 1));
                } elseif (($byte & 240) == 224) {
                    $length = (($byte & 15) << 8) + ord(fread($this->socket, 1));
                    $length = ($length << 8) + ord(fread($this->socket, 1));
                    $length = ($length << 8) + ord(fread($this->socket, 1));
                } else {
                    $length = ord(fread($this->socket, 1));
                    $length = ($length << 8) + ord(fread($this->socket, 1));
                    $length = ($length << 8) + ord(fread($this->socket, 1));
                    $length = ($length << 8) + ord(fread($this->socket, 1));
                }
            } else {
                $length = $byte;
            }

            $_ = "";
            if ($length > 0) {
                $retLen = 0;
                while ($retLen < $length && !feof($this->socket)) {
                    $toRead = $length - $retLen;
                    $_ .= fread($this->socket, $toRead);
                    $retLen = strlen($_);
                }
                $response[] = $_;
            }

            if ($_ == "!done") {
                $receivedDone = true;
            }

            $status = socket_get_status($this->socket);
            if ((!$this->connected && !$status['unread_bytes']) || ($this->connected && !$status['unread_bytes'] && $receivedDone)) {
                break;
            }
        }

        return $parse ? $this->parseResponse($response) : $response;
    }

    public function write(string $command, $param2 = true): bool
    {
        if (!$this->socket || !is_resource($this->socket)) {
            return false;
        }

        if ($command) {
            $data = explode("\n", $command);
            foreach ($data as $com) {
                $com = trim($com);
                fwrite($this->socket, $this->encodeLength(strlen($com)) . $com);
                $this->debug('<<< [' . strlen($com) . '] ' . $com);
            }

            if (is_int($param2)) {
                fwrite($this->socket, $this->encodeLength(strlen('.tag=' . $param2)) . '.tag=' . $param2 . chr(0));
            } elseif (is_bool($param2) && $param2) {
                fwrite($this->socket, chr(0));
            }
            return true;
        }
        return false;
    }

    public function comm(string $com, array $arr = []): array
    {
        $count = count($arr);
        $this->write($com, $count == 0);

        $i = 0;
        foreach ($arr as $k => $v) {
            switch ($k[0]) {
                case "?":
                    $el = "$k=$v";
                    break;
                case "~":
                    $el = "$k~$v";
                    break;
                default:
                    $el = "=$k=$v";
                    break;
            }
            $last = ($i++ == $count - 1);
            $this->write($el, $last);
        }

        return $this->read();
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getLastError(): ?string
    {
        return $this->error_str ?: null;
    }

    // --- INTEGRATED MODULE ACCESSORS ---

    public function hotspot(): Hotspot
    {
        return $this->hotspot ??= new Hotspot($this->getInternalConnection(), $this->db);
    }

    public function voucher(): Voucher
    {
        return $this->voucher ??= new Voucher($this->getInternalConnection(), $this->db);
    }

    public function pppoe(): PPPoE
    {
        return $this->pppoe ??= new PPPoE($this->getInternalConnection(), $this->db);
    }

    public function firewall(): Firewall
    {
        return $this->firewall ??= new Firewall($this->getInternalConnection(), $this->db);
    }

    public function queue(): Queue
    {
        return $this->queue ??= new Queue($this->getInternalConnection(), $this->db);
    }

    public function interface(): NetworkInterface
    {
        return $this->interface ??= new NetworkInterface($this->getInternalConnection(), $this->db);
    }

    public function system(): System
    {
        return $this->system ??= new System($this->getInternalConnection(), $this->db);
    }

    public function ip(): Ip
    {
        return $this->ipModule ??= new Ip($this->getInternalConnection(), $this->db);
    }

    public function backup(): Backup
    {
        return $this->backup ??= new Backup($this->getInternalConnection(), $this->db);
    }

    public function tool(): Tool
    {
        return $this->tool ??= new Tool($this->getInternalConnection(), $this->db);
    }

    public function vpn(): Vpn
    {
        return $this->vpn ??= new Vpn($this->getInternalConnection(), $this->db);
    }

    public function billing(): Billing
    {
        if ($this->billing === null) {
            $this->billing = new Billing($this->getInternalRos());
        }
        return $this->billing;
    }

    public function dnsFilter(): DnsFilter
    {
        return $this->dnsFilter ??= new DnsFilter($this->getInternalConnection(), $this->db);
    }

    public function bot(): BotHandler
    {
        if ($this->bot === null) {
            $this->bot = new BotHandler($this->getInternalRos());
        }
        return $this->bot;
    }

    public function db(): ?Database
    {
        return $this->db;
    }

    public function json(): JsonStorage
    {
        return $this->jsonStorage;
    }

    public function cache(): Cache
    {
        return $this->cache;
    }

    // --- DEDICATED ACTION SHORTCUTS ---

    public function find(): \RouterOSFramework\Actions\Finder
    {
        return $this->getInternalRos()->find();
    }

    public function set(): \RouterOSFramework\Actions\Setter
    {
        return $this->getInternalRos()->set();
    }

    public function get(): \RouterOSFramework\Actions\Getter
    {
        return $this->getInternalRos()->get();
    }

    public function printer(): \RouterOSFramework\Actions\Printer
    {
        return $this->getInternalRos()->printer();
    }

    public function remove(): \RouterOSFramework\Actions\Remover
    {
        return $this->getInternalRos()->remove();
    }

    public function toggle(): \RouterOSFramework\Actions\Toggler
    {
        return $this->getInternalRos()->toggle();
    }

    public function findNameToId(string $name, string $path = '/ip/hotspot/user'): ?string
    {
        return $this->getInternalRos()->findNameToId($name, $path);
    }

    public function findname_toId(string $name, string $path = '/ip/hotspot/user'): ?string
    {
        return $this->getInternalRos()->findNameToId($name, $path);
    }

    public function setLimit(string $username, ?string $limitUptime = null, $limitBytes = null, string $path = '/ip/hotspot/user'): array
    {
        return $this->getInternalRos()->setLimit($username, $limitUptime, $limitBytes, $path);
    }

    public function setComment(string $username, string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->getInternalRos()->setComment($username, $comment, $path);
    }

    public function setcoment(string $username, string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->getInternalRos()->setComment($username, $comment, $path);
    }

    public function setEmail(string $username, string $email, string $path = '/ip/hotspot/user'): array
    {
        return $this->getInternalRos()->setEmail($username, $email, $path);
    }

    public function setName(string $idOrOldName, string $newName, string $path = '/ip/hotspot/user'): array
    {
        return $this->getInternalRos()->setName($idOrOldName, $newName, $path);
    }

    private function getInternalConnection(): Connection
    {
        if ($this->internalConnection === null) {
            $this->internalConnection = new Connection($this->connectionParams, false);
        }
        return $this->internalConnection;
    }

    private function getInternalRos(): RouterOS
    {
        if ($this->internalRos === null) {
            $this->internalRos = new RouterOS($this->connectionParams);
            if ($this->db) {
                $this->internalRos->withDatabase($this->db);
            }
        }
        return $this->internalRos;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
