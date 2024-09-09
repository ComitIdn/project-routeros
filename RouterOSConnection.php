<?php

namespace App;

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\ResponseException;

class RouterOSConnection
{
    private $client;

    public function __construct($host, $user, $password, $port = 8728, $timeout = 10)
    {
        $this->client = new Client([
            'host'     => $host,
            'user'     => $user,
            'password' => $password,
            'port'     => $port,
            'timeout'  => $timeout,
        ]);
    }

    /**
     * Execute a query on RouterOS and return the result.
     *
     * @param Query $query
     * @return array
     */
    public function executeQuery(Query $query): array
    {
        try {
            return $this->client->query($query)->read();
        } catch (ResponseException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * --- PPPoE USER MANAGEMENT ---
     */
    
    // Get all PPPoE users (secrets)
    public function getPppoeUsers(): array
    {
        $query = new Query('/ppp/secret/print');
        return $this->executeQuery($query);
    }

    // Get details of a specific PPPoE user by name
    public function getPppoeUserDetails(string $name): array
    {
        $query = (new Query('/ppp/secret/print'))->where('name', $name);
        return $this->executeQuery($query);
    }

    // Add a PPPoE user (secret)
    public function addPppoeUser(string $name, string $password, string $service = 'pppoe', string $profile = 'default', string $comment = ''): array
    {
        $query = (new Query('/ppp/secret/add'))
        ->equal('name', $name)
            ->equal('password', $password)
            ->equal('service', $service)
            ->equal('profile', $profile);

        if (!empty($comment)) {
            $query->equal('comment', $comment);
        }

        return $this->executeQuery($query);
    }
    
    // Remove a PPPoE user (secret)
    public function removePppoeUser(string $name): array
    {
        $query = (new Query('/ppp/secret/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }

    // Update PPPoE user details (set user)
    public function setPppoeUser(string $name, ?string $password = null, ?string $profile = null, ?string $comment = null): array
    {
        $query = (new Query('/ppp/secret/set'))->equal('name', $name);

        if ($password !== null) {
            $query->equal('password', $password);
        }

        if ($profile !== null) {
            $query->equal('profile', $profile);
        }

        if ($comment !== null) {
            $query->equal('comment', $comment);
        }

        return $this->executeQuery($query);
    }

    // Get all active PPPoE users
    public function getActivePppoeUsers(): array
    {
        $query = new Query('/ppp/active/print');
        return $this->executeQuery($query);
    }

    // Remove an active PPPoE user by session ID
    public function removeActivePppoeUser(string $id): array
    {
        $query = (new Query('/ppp/active/remove'))->equal('.id', $id);
        return $this->executeQuery($query);
    }

    /**
     * --- PPPoE SERVER MANAGEMENT ---
     */

    // Get all PPPoE servers
    public function getPppoeServers(): array
    {
        $query = new Query('/interface/pppoe-server/print');
        return $this->executeQuery($query);
    }

    // Add a PPPoE server
    public function addPppoeServer(string $name, string $interface, string $serviceName): array
    {
        $query = (new Query('/interface/pppoe-server/add'))
        ->equal('name', $name)
            ->equal('interface', $interface)
            ->equal('service-name', $serviceName);
        return $this->executeQuery($query);
    }

    // Remove a PPPoE server
    public function removePppoeServer(string $name): array
    {
        $query = (new Query('/interface/pppoe-server/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }

    /**
     * --- PPPoE SERVER PROFILE MANAGEMENT ---
     */

    // Get all PPPoE profiles
    public function getPppoeProfiles(): array
    {
        $query = new Query('/ppp/profile/print');
        return $this->executeQuery($query);
    }

    // Add a PPPoE profile
    public function addPppoeProfile(string $name, string $localAddress, string $remoteAddress, string $rateLimit = ''): array
    {
        $query = (new Query('/ppp/profile/add'))
        ->equal('name', $name)
            ->equal('local-address', $localAddress)
            ->equal('remote-address', $remoteAddress);

        if (!empty($rateLimit)) {
            $query->equal('rate-limit', $rateLimit);
        }

        return $this->executeQuery($query);
    }

    // Remove a PPPoE profile
    public function removePppoeProfile(string $name): array
    {
        $query = (new Query('/ppp/profile/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }


    /**
     * Monitor internet traffic.
     *
     * @return array
     */
    public function getInternetTraffic(): array
    {
        $query = new Query('/interface/print');
        return $this->executeQuery($query);
    }

    /**
     * --- HOTSPOT MANAGEMENT ---
     */

    // Get all Hotspot users
    public function getHotspotUsers(): array
    {
        $query = new Query('/ip/hotspot/user/print');
        return $this->executeQuery($query);
    }

    /**
     * Add a Hotspot user with more details (username, password, limit-uptime, comment, profile)
     * Example of adding a new user to the Hotspot.
     *
     * @param string $name
     * @param string $password
     * @param string $profile
     * @param string $limit_uptime
     * @return array
     */
    public function addHotspotUser(
        string $name,
        string $password,
        string $profile,
        string $limitUptime = '',
        string $comment = ''
    ): array {
        $query = (new Query('/ip/hotspot/user/add'))
        ->equal('name', $name)
            ->equal('password', $password)
            ->equal('profile', $profile);

        if (!empty($limitUptime)) {
            $query->equal('limit-uptime', $limitUptime);
        }

        if (!empty($comment)) {
            $query->equal('comment', $comment);
        }

        return $this->executeQuery($query);
    }
    
    // Set (update) Hotspot user details
    public function setHotspotUser(
        string $name,
        ?string $password = null,
        ?string $limitUptime = null,
        ?string $comment = null,
        ?string $profile = null
    ): array {
        $query = (new Query('/ip/hotspot/user/set'))->equal('name', $name);

        if ($password !== null) {
            $query->equal('password', $password);
        }

        if ($limitUptime !== null) {
            $query->equal('limit-uptime', $limitUptime);
        }

        if ($comment !== null) {
            $query->equal('comment', $comment);
        }

        if ($profile !== null) {
            $query->equal('profile', $profile);
        }

        return $this->executeQuery($query);
    }

    // Get specific Hotspot user details by name
    public function getHotspotUserDetails(string $name): array
    {
        $query = (new Query('/ip/hotspot/user/print'))->where('name', $name);
        return $this->executeQuery($query);
    }

    // Reset counter for a Hotspot user
    public function resetHotspotUserCounter(string $name): array
    {
        $query = (new Query('/ip/hotspot/user/reset-counters'))->equal('name', $name);
        return $this->executeQuery($query);
    }
    
    // Remove a Hotspot user
    public function removeHotspotUser(string $name): array
    {
        $query = (new Query('/ip/hotspot/user/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }

    // Get active Hotspot users
    public function getActiveHotspotUsers(): array
    {
        $query = new Query('/ip/hotspot/active/print');
        return $this->executeQuery($query);
    }

    // Remove active Hotspot user by session ID
    public function removeActiveHotspotUser(string $id): array
    {
        $query = (new Query('/ip/hotspot/active/remove'))->equal('.id', $id);
        return $this->executeQuery($query);
    }

    /**
     * Get expired Hotspot users based on uptime limit (e.g., `00:00:01`).
     *
     * @return array
     */
    public function getExpiredHotspotUsers(): array
    {
        $query = new Query('/ip/hotspot/user/print')->where('uptime', '00:00:01');
        return $this->executeQuery($query);
    }

    /**
     * --- HOTSPOT SERVER ---
     */

    // Get all Hotspot servers
    public function getHotspotServers(): array
    {
        $query = new Query('/ip/hotspot/print');
        return $this->executeQuery($query);
    }

    // Add a Hotspot server
    public function addHotspotServer(string $name, string $interface, string $profile): array
    {
        $query = (new Query('/ip/hotspot/add'))
        ->equal('name', $name)
            ->equal('interface', $interface)
            ->equal('profile', $profile);
        return $this->executeQuery($query);
    }

    // Remove a Hotspot server
    public function removeHotspotServer(string $name): array
    {
        $query = (new Query('/ip/hotspot/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }

    /**
     * --- HOTSPOT SERVER PROFILES ---
     */

    // Get all Hotspot server profiles
    public function getHotspotServerProfiles(): array
    {
        $query = new Query('/ip/hotspot/profile/print');
        return $this->executeQuery($query);
    }

    // Add a Hotspot server profile
    public function addHotspotServerProfile(string $name, string $dnsName, string $loginBy): array
    {
        $query = (new Query('/ip/hotspot/profile/add'))
        ->equal('name', $name)
            ->equal('dns-name', $dnsName)
            ->equal('login-by', $loginBy);
        return $this->executeQuery($query);
    }

    // Remove a Hotspot server profile
    public function removeHotspotServerProfile(string $name): array
    {
        $query = (new Query('/ip/hotspot/profile/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }

    /**
     * --- HOTSPOT IP BINDINGS ---
     */

    // Get all Hotspot IP bindings
    public function getHotspotIpBindings(): array
    {
        $query = new Query('/ip/hotspot/ip-binding/print');
        return $this->executeQuery($query);
    }

    // Add a Hotspot IP binding
    public function addHotspotIpBinding(string $macAddress, string $type, string $address): array
    {
        $query = (new Query('/ip/hotspot/ip-binding/add'))
        ->equal('mac-address', $macAddress)
            ->equal('type', $type)
            ->equal('address', $address);
        return $this->executeQuery($query);
    }

    // Remove a Hotspot IP binding
    public function removeHotspotIpBinding(string $id): array
    {
        $query = (new Query('/ip/hotspot/ip-binding/remove'))->equal('.id', $id);
        return $this->executeQuery($query);
    }

    /**
     * --- HOTSPOT USERS PROFILES ---
     */

    // Get all Hotspot user profiles
    public function getHotspotUserProfiles(): array
    {
        $query = new Query('/ip/hotspot/user/profile/print');
        return $this->executeQuery($query);
    }

    // Add a Hotspot user profile
    public function addHotspotUserProfile(string $name, string $rateLimit, string $sharedUsers): array
    {
        $query = (new Query('/ip/hotspot/user/profile/add'))
        ->equal('name', $name)
            ->equal('rate-limit', $rateLimit)
            ->equal('shared-users', $sharedUsers);
        return $this->executeQuery($query);
    }

    // Remove a Hotspot user profile
    public function removeHotspotUserProfile(string $name): array
    {
        $query = (new Query('/ip/hotspot/user/profile/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }

    /**
     * --- HOTSPOT WALLED GARDEN ---
     */

    // Get all Walled Garden rules
    public function getWalledGardenRules(): array
    {
        $query = new Query('/ip/hotspot/walled-garden/print');
        return $this->executeQuery($query);
    }

    // Add a Walled Garden rule
    public function addWalledGardenRule(string $dstHost, string $dstPort, string $action): array
    {
        $query = (new Query('/ip/hotspot/walled-garden/add'))
        ->equal('dst-host', $dstHost)
            ->equal('dst-port', $dstPort)
            ->equal('action', $action);
        return $this->executeQuery($query);
    }

    // Remove a Walled Garden rule
    public function removeWalledGardenRule(string $id): array
    {
        $query = (new Query('/ip/hotspot/walled-garden/remove'))->equal('.id', $id);
        return $this->executeQuery($query);
    }

    /**
     * --- HOTSPOT WALLED GARDEN IP ---
     */

    // Get all Walled Garden IP rules
    public function getWalledGardenIpRules(): array
    {
        $query = new Query('/ip/hotspot/walled-garden/ip/print');
        return $this->executeQuery($query);
    }

    // Add a Walled Garden IP rule
    public function addWalledGardenIpRule(string $dstAddress, string $dstPort, string $action): array
    {
        $query = (new Query('/ip/hotspot/walled-garden/ip/add'))
        ->equal('dst-address', $dstAddress)
            ->equal('dst-port', $dstPort)
            ->equal('action', $action);
        return $this->executeQuery($query);
    }

    // Remove a Walled Garden IP rule
    public function removeWalledGardenIpRule(string $id): array
    {
        $query = (new Query('/ip/hotspot/walled-garden/ip/remove'))->equal('.id', $id);
        return $this->executeQuery($query);
    }
    
    /**
     * --- SYSTEM SCRIPTS METHODS ---
     */

    // Get router identity.
    public function getRouterIdentity(): array
    {
        $query = new Query('/system/identity/print');
        return $this->executeQuery($query);
    }

    // Get system date and time.
    public function getSystemDateTime(): array
    {
        $query = new Query('/system/clock/print');
        return $this->executeQuery($query);
    }
    
    // Add system script
    public function addSystemScript(string $name, string $source): array
    {
        $query = (new Query('/system/script/add'))
        ->equal('name', $name)
            ->equal('source', $source);
        return $this->executeQuery($query);
    }
    
    // Remove system script
    public function removeSystemScript(string $name): array
    {
        $query = (new Query('/system/script/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }

    // Get all system scripts
    public function getSystemScripts(): array
    {
        $query = new Query('/system/script/print');
        return $this->executeQuery($query);
    }

    // Get specific script details (e.g., by name, comment, owner)
    public function getSystemScriptByName(string $name): array
    {
        $query = (new Query('/system/script/print'))->where('name', $name);
        return $this->executeQuery($query);
    }

    // Get system job details
    public function getSystemJobs(): array
    {
        $query = new Query('/system/job/print');
        return $this->executeQuery($query);
    }

    // Get system environment variables
    public function getSystemEnvironment(): array
    {
        $query = new Query('/system/environment/print');
        return $this->executeQuery($query);
    }

    /**
     * --- SCHEDULER METHODS ---
     */

    // Add a scheduler
    public function addScheduler(string $name, string $onEvent, string $startDate, string $startTime, string $interval): array
    {
        $query = (new Query('/system/scheduler/add'))
        ->equal('name', $name)
            ->equal('on-event', $onEvent)
            ->equal('start-date', $startDate)
            ->equal('start-time', $startTime)
            ->equal('interval', $interval);
        return $this->executeQuery($query);
    }

    // Remove a scheduler
    public function removeScheduler(string $name): array
    {
        $query = (new Query('/system/scheduler/remove'))->equal('name', $name);
        return $this->executeQuery($query);
    }
    
    /**
     * --- FIREWALL METHODS ---
     */

    // Get all firewall filter rules
    public function getFirewallFilterRules(): array
    {
        $query = new Query('/ip/firewall/filter/print');
        return $this->executeQuery($query);
    }

    // Get all firewall NAT rules
    public function getFirewallNatRules(): array
    {
        $query = new Query('/ip/firewall/nat/print');
        return $this->executeQuery($query);
    }

    // Get all firewall Mangle rules
    public function getFirewallMangleRules(): array
    {
        $query = new Query('/ip/firewall/mangle/print');
        return $this->executeQuery($query);
    }

    // Get all firewall RAW rules
    public function getFirewallRawRules(): array
    {
        $query = new Query('/ip/firewall/raw/print');
        return $this->executeQuery($query);
    }

    /**
     * --- DNS METHODS ---
     */

    // Get all DNS cache entries
    public function getDnsCache(): array
    {
        $query = new Query('/ip/dns/cache/print');
        return $this->executeQuery($query);
    }

    // Get DNS settings
    public function getDnsSettings(): array
    {
        $query = new Query('/ip/dns/print');
        return $this->executeQuery($query);
    }

    /**
     * --- SYSTEM RESOURCES AND REBOOT ---
     */

    // Get system resources (CPU, memory, uptime, etc.)
    public function getSystemResources(): array
    {
        $query = new Query('/system/resource/print');
        return $this->executeQuery($query);
    }

    // Reboot the router
    public function rebootRouter(): array
    {
        $query = new Query('/system/reboot');
        return $this->executeQuery($query);
    }

    /**
     * --- QUEUES METHODS ---
     */

    // Get all queues
    public function getQueues(): array
    {
        $query = new Query('/queue/simple/print');
        return $this->executeQuery($query);
    }

    // Add a queue
    public function addQueue(string $name, string $target, string $maxLimit): array
    {
        $query = (new Query('/queue/simple/add'))
        ->equal('name', $name)
            ->equal('target', $target)
            ->equal('max-limit', $maxLimit);
        return $this->executeQuery($query);
    }

    /**
     * --- TOOL METHODS ---
     */

    // Ping a specific IP address
    public function ping(string $address): array
    {
        $query = (new Query('/ping'))->equal('address', $address);
        return $this->executeQuery($query);
    }

    // Get active Netwatch entries
    public function getNetwatch(): array
    {
        $query = new Query('/tool/netwatch/print');
        return $this->executeQuery($query);
    }

    /**
     * --- LOG METHODS ---
     */

    // Get system logs
    public function getLogs(): array
    {
        $query = new Query('/log/print');
        return $this->executeQuery($query);
    }

    /**
     * --- SYSTEM PROFILES METHODS ---
     */

    // Get system CPU profile information
    public function getCpuProfile(): array
    {
        $query = new Query('/tool/profile/print');
        return $this->executeQuery($query);
    }
    
    /**
     * Custom query execution.
     *
     * @param string $command
     * @return array
     */
    public function customCommand(string $command): array
    {
        $query = new Query($command);
        return $this->executeQuery($query);
    }
}