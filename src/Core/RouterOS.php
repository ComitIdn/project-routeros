<?php

namespace RouterOSFramework\Core;

use RouterOS\Query;
use RouterOSFramework\Actions\Creator;
use RouterOSFramework\Actions\Finder;
use RouterOSFramework\Actions\Getter;
use RouterOSFramework\Actions\Printer;
use RouterOSFramework\Actions\Reader;
use RouterOSFramework\Actions\Remover;
use RouterOSFramework\Actions\Setter;
use RouterOSFramework\Actions\Toggler;
use RouterOSFramework\Actions\Writer;
use RouterOSFramework\Modules\Backup;
use RouterOSFramework\Modules\Billing;
use RouterOSFramework\Modules\BotHandler;
use RouterOSFramework\Modules\DnsFilter;
use RouterOSFramework\Modules\Firewall;
use RouterOSFramework\Modules\Hotspot;
use RouterOSFramework\Modules\Ip;
use RouterOSFramework\Modules\NetworkInterface;
use RouterOSFramework\Modules\PPPoE;
use RouterOSFramework\Modules\Ptmp;
use RouterOSFramework\Modules\Queue;
use RouterOSFramework\Modules\System;
use RouterOSFramework\Modules\Tool;
use RouterOSFramework\Modules\Voucher;
use RouterOSFramework\Modules\Vpn;

/**
 * Class RouterOS
 * Main entry point and unified facade for the MikroTik RouterOS Framework.
 * Supports RouterOS v6.xx, v7.xx+, Multi-Protocol Transports (API, REST, SSH, Telnet)
 * and Point-to-Multipoint (PTMP) wireless networks.
 */
class RouterOS
{
    private Connection $connection;
    private ?Database $db = null;
    private JsonStorage $jsonStorage;
    private Cache $cache;

    // Module instances (lazy loaded)
    private ?Hotspot $hotspot = null;
    private ?Voucher $voucher = null;
    private ?PPPoE $pppoe = null;
    private ?Firewall $firewall = null;
    private ?Queue $queue = null;
    private ?NetworkInterface $interface = null;
    private ?System $system = null;
    private ?Ip $ip = null;
    private ?BotHandler $bot = null;
    private ?MultiRouter $multi = null;
    private ?Backup $backup = null;
    private ?Tool $tool = null;
    private ?Ptmp $ptmp = null;
    private ?Vpn $vpn = null;
    private ?Billing $billing = null;
    private ?DnsFilter $dnsFilter = null;

    // Action instances (lazy loaded)
    private ?Creator $creator = null;
    private ?Writer $writer = null;
    private ?Reader $reader = null;
    private ?Finder $finder = null;
    private ?Setter $setter = null;
    private ?Getter $getter = null;
    private ?Printer $printer = null;
    private ?Remover $remover = null;
    private ?Toggler $toggler = null;

    /**
     * RouterOS constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->connection = new Connection($config);
        $this->jsonStorage = new JsonStorage();
        $this->cache = new Cache();

        if (!empty($config['database']) && is_array($config['database'])) {
            $this->db = new Database($config['database']);
        } elseif (isset($config['enable_database']) && $config['enable_database']) {
            $this->db = new Database([]);
        }
    }

    public static function connect(array $config = []): self
    {
        return new self($config);
    }

    public static function withConfig(string $configFile = 'config.php'): self
    {
        $path = file_exists($configFile) ? $configFile : (__DIR__ . '/../../' . ltrim($configFile, '/'));
        $config = file_exists($path) ? require $path : [];

        $routerConfig = $config['mikrotik'] ?? [];
        if (!empty($config['database'])) {
            $routerConfig['database'] = $config['database'];
        }

        return new self($routerConfig);
    }

    public function withDatabase($db): self
    {
        if ($db instanceof Database) {
            $this->db = $db;
        } elseif (is_array($db)) {
            $this->db = new Database($db);
        } elseif (is_string($db)) {
            $this->db = new Database(['driver' => 'sqlite', 'file' => $db]);
        }

        // Reset modules so they rebind database
        $this->hotspot = null;
        $this->voucher = null;
        $this->pppoe = null;
        $this->firewall = null;
        $this->queue = null;
        $this->interface = null;
        $this->system = null;
        $this->ip = null;
        $this->bot = null;
        $this->backup = null;
        $this->tool = null;
        $this->ptmp = null;
        $this->vpn = null;
        $this->billing = null;
        $this->dnsFilter = null;

        return $this;
    }

    // --- PROTOCOL SWITCHING ---

    public function setProtocol(string $protocol, ?int $port = null): bool
    {
        return $this->connection->setProtocol($protocol, $port);
    }

    public function getProtocol(): string
    {
        return $this->connection->getProtocol();
    }

    public function useRest(?int $port = null): self
    {
        $this->connection->setProtocol('rest', $port);
        return $this;
    }

    public function useSsh(?int $port = null): self
    {
        $this->connection->setProtocol('ssh', $port);
        return $this;
    }

    public function useTelnet(?int $port = null): self
    {
        $this->connection->setProtocol('telnet', $port);
        return $this;
    }

    public function useApi(?int $port = null): self
    {
        $this->connection->setProtocol('api', $port);
        return $this;
    }

    public function useAuto(): self
    {
        $this->connection->setProtocol('auto');
        return $this;
    }

    // --- MODULE ACCESSORS ---

    public function hotspot(): Hotspot
    {
        return $this->hotspot ??= new Hotspot($this->connection, $this->db);
    }

    public function voucher(): Voucher
    {
        return $this->voucher ??= new Voucher($this->connection, $this->db);
    }

    public function pppoe(): PPPoE
    {
        return $this->pppoe ??= new PPPoE($this->connection, $this->db);
    }

    public function ptmp(): Ptmp
    {
        return $this->ptmp ??= new Ptmp($this->connection, $this->db);
    }

    public function firewall(): Firewall
    {
        return $this->firewall ??= new Firewall($this->connection, $this->db);
    }

    public function queue(): Queue
    {
        return $this->queue ??= new Queue($this->connection, $this->db);
    }

    public function interface(): NetworkInterface
    {
        return $this->interface ??= new NetworkInterface($this->connection, $this->db);
    }

    public function system(): System
    {
        return $this->system ??= new System($this->connection, $this->db);
    }

    public function ip(): Ip
    {
        return $this->ip ??= new Ip($this->connection, $this->db);
    }

    public function bot(): BotHandler
    {
        return $this->bot ??= new BotHandler($this);
    }

    public function backup(): Backup
    {
        return $this->backup ??= new Backup($this->connection, $this->db);
    }

    public function tool(): Tool
    {
        return $this->tool ??= new Tool($this->connection, $this->db);
    }

    public function vpn(): Vpn
    {
        return $this->vpn ??= new Vpn($this->connection, $this->db);
    }

    public function billing(): Billing
    {
        return $this->billing ??= new Billing($this);
    }

    public function dnsFilter(): DnsFilter
    {
        return $this->dnsFilter ??= new DnsFilter($this->connection, $this->db);
    }

    public function multi(): MultiRouter
    {
        return $this->multi ??= new MultiRouter($this->db, $this->jsonStorage);
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

    public function response(): Response
    {
        return new Response();
    }

    public function hasDatabase(): bool
    {
        return $this->db !== null;
    }

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }

    public function getConnectionError(): ?string
    {
        return $this->connection->getConnectionError();
    }

    // --- FULL OPERATIONAL ACTION MODULES (CRUD & BEYOND) ---

    public function create(): Creator
    {
        return $this->creator ??= new Creator($this->connection, $this->find());
    }

    public function creator(): Creator
    {
        return $this->create();
    }

    public function write(): Writer
    {
        return $this->writer ??= new Writer($this->connection);
    }

    public function writer(): Writer
    {
        return $this->write();
    }

    public function read(): Reader
    {
        return $this->reader ??= new Reader($this->connection);
    }

    public function reader(): Reader
    {
        return $this->read();
    }

    public function find(): Finder
    {
        return $this->finder ??= new Finder($this->connection);
    }

    public function finder(): Finder
    {
        return $this->find();
    }

    public function set(): Setter
    {
        return $this->setter ??= new Setter($this->connection, $this->find());
    }

    public function setter(): Setter
    {
        return $this->set();
    }

    public function get(): Getter
    {
        return $this->getter ??= new Getter($this->connection, $this->find());
    }

    public function getter(): Getter
    {
        return $this->get();
    }

    public function print(): Printer
    {
        return $this->printer ??= new Printer($this->connection, $this->find());
    }

    public function printer(): Printer
    {
        return $this->print();
    }

    public function remove(): Remover
    {
        return $this->remover ??= new Remover($this->connection, $this->find());
    }

    public function remover(): Remover
    {
        return $this->remove();
    }

    public function toggle(): Toggler
    {
        return $this->toggler ??= new Toggler($this->connection, $this->find());
    }

    public function toggler(): Toggler
    {
        return $this->toggle();
    }

    // --- DIRECT SUPERPOWER SHORTCUTS ---

    public function findNameToId(string $name, string $path = '/ip/hotspot/user'): ?string
    {
        return $this->find()->nameToId($name, $path);
    }

    public function findname_toId(string $name, string $path = '/ip/hotspot/user'): ?string
    {
        return $this->find()->nameToId($name, $path);
    }

    public function setLimit(string $username, ?string $limitUptime = null, $limitBytes = null, string $path = '/ip/hotspot/user'): array
    {
        return $this->set()->limit($username, $limitUptime, $limitBytes, $path);
    }

    public function setComment(string $username, string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->set()->comment($username, $comment, $path);
    }

    public function setcoment(string $username, string $comment, string $path = '/ip/hotspot/user'): array
    {
        return $this->setComment($username, $comment, $path);
    }

    public function setEmail(string $username, string $email, string $path = '/ip/hotspot/user'): array
    {
        return $this->set()->email($username, $email, $path);
    }

    public function setName(string $idOrOldName, string $newName, string $path = '/ip/hotspot/user'): array
    {
        return $this->set()->name($idOrOldName, $newName, $path);
    }

    public function setPassword(string $username, string $password, string $path = '/ip/hotspot/user'): array
    {
        return $this->set()->password($username, $password, $path);
    }

    public function setProfile(string $username, string $profile, string $path = '/ip/hotspot/user'): array
    {
        return $this->set()->profile($username, $profile, $path);
    }

    public function customCommand(string $command): array
    {
        $query = new Query($command);
        return $this->connection->executeQuery($query);
    }
}
