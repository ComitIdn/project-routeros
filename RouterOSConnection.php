<?php

namespace App;

use RouterOS\Client;
use RouterOS\Query;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;
use RouterOSFramework\Core\RouterOS;

/**
 * Class RouterOSConnection
 * Professional Backward-Compatibility Facade powered by RouterOS Framework.
 * Completely eliminates code duplication by delegating cleanly to underlying modules.
 */
class RouterOSConnection
{
    private RouterOS $framework;

    /**
     * RouterOSConnection constructor.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param int $port
     * @param int $timeout
     * @param bool $autoConnect
     * @param array|null $databaseConfig
     */
    public function __construct(
        $host,
        $user,
        $password,
        $port = 8728,
        $timeout = 3,
        bool $autoConnect = true,
        ?array $databaseConfig = null
    ) {
        $config = [
            'host'    => $host,
            'user'    => $user,
            'pass'    => $password,
            'port'    => (int)$port,
            'timeout' => (int)$timeout,
        ];

        if ($databaseConfig !== null) {
            $config['database'] = $databaseConfig;
        } else {
            // Auto-load config.php database if exists
            $configFile = __DIR__ . '/config.php';
            if (file_exists($configFile)) {
                $cfg = require $configFile;
                if (!empty($cfg['database'])) {
                    $config['database'] = $cfg['database'];
                }
            }
        }

        $this->framework = new RouterOS($config);
    }

    /**
     * Get underlying unified framework instance.
     */
    public function framework(): RouterOS
    {
        return $this->framework;
    }

    // --- MODULE ACCESSORS ---
    public function db(): ?Database { return $this->framework->db(); }
    public function json(): \RouterOSFramework\Core\JsonStorage { return $this->framework->json(); }
    public function cache(): \RouterOSFramework\Core\Cache { return $this->framework->cache(); }
    public function hotspot(): \RouterOSFramework\Modules\Hotspot { return $this->framework->hotspot(); }
    public function voucher(): \RouterOSFramework\Modules\Voucher { return $this->framework->voucher(); }
    public function pppoe(): \RouterOSFramework\Modules\PPPoE { return $this->framework->pppoe(); }
    public function firewall(): \RouterOSFramework\Modules\Firewall { return $this->framework->firewall(); }
    public function queue(): \RouterOSFramework\Modules\Queue { return $this->framework->queue(); }
    public function interface(): \RouterOSFramework\Modules\NetworkInterface { return $this->framework->interface(); }
    public function system(): \RouterOSFramework\Modules\System { return $this->framework->system(); }
    public function ip(): \RouterOSFramework\Modules\Ip { return $this->framework->ip(); }
    public function backup(): \RouterOSFramework\Modules\Backup { return $this->framework->backup(); }
    public function tool(): \RouterOSFramework\Modules\Tool { return $this->framework->tool(); }
    public function vpn(): \RouterOSFramework\Modules\Vpn { return $this->framework->vpn(); }
    public function billing(): \RouterOSFramework\Modules\Billing { return $this->framework->billing(); }
    public function dnsFilter(): \RouterOSFramework\Modules\DnsFilter { return $this->framework->dnsFilter(); }
    public function bot(): \RouterOSFramework\Modules\BotHandler { return $this->framework->bot(); }

    // --- ACTION ACCESSORS ---
    public function find(): \RouterOSFramework\Actions\Finder { return $this->framework->find(); }
    public function set(): \RouterOSFramework\Actions\Setter { return $this->framework->set(); }
    public function get(): \RouterOSFramework\Actions\Getter { return $this->framework->get(); }
    public function printer(): \RouterOSFramework\Actions\Printer { return $this->framework->printer(); }
    public function remove(): \RouterOSFramework\Actions\Remover { return $this->framework->remove(); }
    public function toggle(): \RouterOSFramework\Actions\Toggler { return $this->framework->toggle(); }

    // --- CORE CONNECTION STATUS ---
    public function isConnected(): bool { return $this->framework->isConnected(); }
    public function getConnectionError(): ?string { return $this->framework->getConnectionError(); }
    public function getClient(): ?Client { return $this->framework->connection()->getClient(); }
    public function executeQuery(Query $query): array { return $this->framework->connection()->executeQuery($query); }
    public function customCommand(string $command): array { return $this->framework->customCommand($command); }

    // --- UTILITY METHODS ---
    public function formatBytes($bytes, int $precision = 2): string
    {
        return Helper::formatBytes($bytes, $precision);
    }

    // --- COMMON LEGACY BRIDGES (HOTSPOT & PPPOE) ---

    public function getHotspotUsers(): array { return $this->framework->hotspot()->getUsers(); }
    public function getHotspotUserDetails(string $name): array { return $this->framework->hotspot()->getUserDetails($name); }
    public function addHotspotUser(string $name, string $password, string $profile = 'default', string $limitUptime = '', string $comment = ''): array
    {
        return $this->framework->hotspot()->addUser($name, $password, $profile, $limitUptime, $comment);
    }
    public function setHotspotUser(string $name, ?string $password = null, ?string $limitUptime = null, ?string $comment = null, ?string $profile = null, ?string $limitBytesTotal = null): array
    {
        return $this->framework->hotspot()->setUser($name, $password, $limitUptime, $comment, $profile, $limitBytesTotal);
    }
    public function removeHotspotUser(string $name): array { return $this->framework->hotspot()->removeUser($name); }
    public function disconnectHotspotUser(string $username): array { return $this->framework->hotspot()->disconnectUser($username); }

    public function getPppoeUsers(): array { return $this->framework->pppoe()->getUsers(); }
    public function getPppoeUserDetails(string $name): array { return $this->framework->pppoe()->getUserDetails($name); }
    public function addPppoeUser(string $name, string $password, string $service = 'pppoe', string $profile = 'default', string $comment = ''): array
    {
        return $this->framework->pppoe()->addUser($name, $password, $service, $profile, '', '', $comment);
    }
    public function setPppoeUser(string $name, ?string $password = null, ?string $profile = null, ?string $comment = null): array
    {
        return $this->framework->pppoe()->setUser($name, $password, $profile, $comment);
    }
    public function removePppoeUser(string $name): array { return $this->framework->pppoe()->removeUser($name); }
    public function disconnectPppoeUser(string $username): array { return $this->framework->pppoe()->disconnectUser($username); }

    public function generateHotspotVouchers(int $count, array $options = []): array
    {
        return $this->framework->voucher()->generateAndSave($count, $options);
    }

    /**
     * Dynamic proxy to handle any method dynamically without code bloat.
     */
    public function __call(string $method, array $arguments)
    {
        // 1. Check direct method on RouterOS facade
        if (method_exists($this->framework, $method)) {
            return $this->framework->{$method}(...$arguments);
        }

        // 2. Check modules and action classes
        $modules = [
            $this->framework->hotspot(),
            $this->framework->voucher(),
            $this->framework->pppoe(),
            $this->framework->find(),
            $this->framework->set(),
            $this->framework->get(),
            $this->framework->printer(),
            $this->framework->remove(),
            $this->framework->toggle(),
            $this->framework->system(),
            $this->framework->firewall(),
            $this->framework->queue(),
            $this->framework->interface(),
            $this->framework->ip(),
            $this->framework->backup(),
            $this->framework->tool(),
            $this->framework->vpn(),
        ];

        foreach ($modules as $module) {
            if (method_exists($module, $method)) {
                return $module->{$method}(...$arguments);
            }
        }

        // 3. Fallback: Strip legacy module prefixes (e.g. getHotspotActiveTraffic -> getActiveTraffic)
        $prefixes = ['Hotspot', 'Pppoe', 'Firewall', 'Queue', 'System', 'Vpn', 'Backup', 'Tool', 'Interface'];
        foreach ($prefixes as $p) {
            if (str_contains($method, $p)) {
                $stripped = lcfirst(str_replace($p, '', $method));
                foreach ($modules as $module) {
                    if (method_exists($module, $stripped)) {
                        return $module->{$stripped}(...$arguments);
                    }
                }
            }
        }

        throw new \BadMethodCallException("Method '{$method}' not found on RouterOSConnection or underlying framework modules.");
    }
}
