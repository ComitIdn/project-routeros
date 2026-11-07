<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class Hotspot
 * Comprehensive Hotspot management module for MikroTik RouterOS.
 */
class Hotspot
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * =========================================================================
     * --- HOTSPOT USER MANAGEMENT ---
     * =========================================================================
     */

    public function getUsers(): array
    {
        return $this->conn->read('/ip/hotspot/user/print');
    }

    public function getUserDetails(string $name): array
    {
        return $this->conn->read('/ip/hotspot/user/print', ['name' => $name]);
    }

    public function getUsersByProfile(string $profile): array
    {
        return $this->conn->read('/ip/hotspot/user/print', ['profile' => $profile]);
    }

    public function getUsersByServer(string $server): array
    {
        return $this->conn->read('/ip/hotspot/user/print', ['server' => $server]);
    }

    public function getUsersByComment(string $comment): array
    {
        return $this->conn->read('/ip/hotspot/user/print', ['comment' => $comment]);
    }

    public function addUser(
        string $name,
        string $password,
        string $profile = 'default',
        string $limitUptime = '',
        string $comment = ''
    ): array {
        $params = [
            'name'     => $name,
            'password' => $password,
            'profile'  => $profile,
        ];
        if (!empty($limitUptime)) $params['limit-uptime'] = $limitUptime;
        if (!empty($comment)) $params['comment'] = $comment;

        $res = $this->conn->write('/ip/hotspot/user/add', $params);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('Hotspot', 'Add User', $name, "Profile: {$profile}", 'success');
        }

        return $res;
    }

    public function addUserDetailed(array $params): array
    {
        if (empty($params['name'])) {
            return ['error' => 'Parameter "name" is required.'];
        }

        $res = $this->conn->write('/ip/hotspot/user/add', $params);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('Hotspot', 'Add Detailed User', (string)$params['name'], json_encode($params), 'success');
        }

        return $res;
    }

    public function setUser(
        string $name,
        ?string $password = null,
        ?string $limitUptime = null,
        ?string $comment = null,
        ?string $profile = null,
        ?string $limitBytesTotal = null
    ): array {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/ip/hotspot/user/print', $name) ?? $name);

        $params = [];
        if ($password !== null) $params['password'] = $password;
        if ($limitUptime !== null) $params['limit-uptime'] = $limitUptime;
        if ($comment !== null) $params['comment'] = $comment;
        if ($profile !== null) $params['profile'] = $profile;
        if ($limitBytesTotal !== null) $params['limit-bytes-total'] = $limitBytesTotal;

        return $this->conn->set('/ip/hotspot/user/set', $id, $params);
    }

    public function findNameToId(string $name): ?string
    {
        if (str_starts_with($name, '*')) return $name;
        return $this->conn->findIdByName('/ip/hotspot/user/print', $name);
    }

    public function findname_toId(string $name): ?string
    {
        return $this->findNameToId($name);
    }

    public function setLimit(string $name, ?string $limitUptime = null, $limitBytes = null): array
    {
        $id = $this->findNameToId($name) ?? $name;
        $params = [];
        if ($limitUptime !== null) $params['limit-uptime'] = $limitUptime;
        if ($limitBytes !== null) $params['limit-bytes-total'] = (string)$limitBytes;
        return $this->conn->set('/ip/hotspot/user/set', $id, $params);
    }

    public function setComment(string $name, string $comment): array
    {
        $id = $this->findNameToId($name) ?? $name;
        return $this->conn->set('/ip/hotspot/user/set', $id, ['comment' => $comment]);
    }

    public function setcoment(string $name, string $comment): array
    {
        return $this->setComment($name, $comment);
    }

    public function setEmail(string $name, string $email): array
    {
        $id = $this->findNameToId($name) ?? $name;
        return $this->conn->set('/ip/hotspot/user/set', $id, ['email' => $email]);
    }

    public function setName(string $idOrOldName, string $newName): array
    {
        $id = $this->findNameToId($idOrOldName) ?? $idOrOldName;
        return $this->conn->set('/ip/hotspot/user/set', $id, ['name' => $newName]);
    }

    public function setPassword(string $name, string $password): array
    {
        $id = $this->findNameToId($name) ?? $name;
        return $this->conn->set('/ip/hotspot/user/set', $id, ['password' => $password]);
    }

    public function setProfile(string $name, string $profile): array
    {
        $id = $this->findNameToId($name) ?? $name;
        return $this->conn->set('/ip/hotspot/user/set', $id, ['profile' => $profile]);
    }

    public function enableUser(string $idOrName): array
    {
        $id = str_starts_with($idOrName, '*') ? $idOrName : ($this->conn->findIdByName('/ip/hotspot/user/print', $idOrName) ?? $idOrName);
        return $this->conn->enable('/ip/hotspot/user/enable', $id);
    }

    public function disableUser(string $idOrName): array
    {
        $id = str_starts_with($idOrName, '*') ? $idOrName : ($this->conn->findIdByName('/ip/hotspot/user/print', $idOrName) ?? $idOrName);
        return $this->conn->disable('/ip/hotspot/user/disable', $id);
    }

    public function resetUserCounter(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/ip/hotspot/user/print', $name) ?? $name);
        return $this->conn->write('/ip/hotspot/user/reset-counters', ['.id' => $id]);
    }

    public function removeUser(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/ip/hotspot/user/print', $name) ?? $name);
        $res = $this->conn->remove('/ip/hotspot/user/remove', $id);

        if ($this->db && !isset($res['error'])) {
            $this->db->log('Hotspot', 'Remove User', $name, "Removed user {$name}", 'success');
        }

        return $res;
    }

    public function removeUsersByComment(string $comment): array
    {
        $users = $this->getUsersByComment($comment);
        if (isset($users['error']) || empty($users)) {
            return ['removed' => 0, 'status' => 'No users found matching comment'];
        }

        $count = 0;
        foreach ($users as $user) {
            if (isset($user['.id'])) {
                $this->conn->remove('/ip/hotspot/user/remove', $user['.id']);
                $count++;
            }
        }

        return ['removed' => $count, 'comment' => $comment];
    }

    public function removeUsersByProfile(string $profile): array
    {
        $users = $this->getUsersByProfile($profile);
        if (isset($users['error']) || empty($users)) {
            return ['removed' => 0, 'status' => 'No users found matching profile'];
        }

        $count = 0;
        foreach ($users as $user) {
            if (isset($user['.id'])) {
                $this->conn->remove('/ip/hotspot/user/remove', $user['.id']);
                $count++;
            }
        }

        return ['removed' => $count, 'profile' => $profile];
    }

    public function getExpiredUsers(): array
    {
        $users = $this->getUsers();
        if (isset($users['error']) || !is_array($users)) {
            return $users;
        }

        $expired = [];
        foreach ($users as $user) {
            $isExpired = false;

            if (isset($user['disabled']) && $user['disabled'] === 'true') {
                $isExpired = true;
            }

            if (!empty($user['limit-uptime']) && isset($user['uptime']) && $user['uptime'] === $user['limit-uptime']) {
                $isExpired = true;
            }

            if (!empty($user['limit-bytes-total']) && isset($user['bytes-in'], $user['bytes-out'])) {
                $totalBytes = (float)$user['bytes-in'] + (float)$user['bytes-out'];
                if ($totalBytes >= (float)$user['limit-bytes-total']) {
                    $isExpired = true;
                }
            }

            if (isset($user['comment']) && stripos($user['comment'], 'expired') !== false) {
                $isExpired = true;
            }

            if ($isExpired) {
                $expired[] = $user;
            }
        }

        return $expired;
    }

    public function removeExpiredUsers(): array
    {
        $expiredUsers = $this->getExpiredUsers();
        if (isset($expiredUsers['error']) || empty($expiredUsers)) {
            return ['removed' => 0, 'status' => 'No expired users found'];
        }

        $count = 0;
        foreach ($expiredUsers as $user) {
            if (isset($user['.id'])) {
                $this->conn->remove('/ip/hotspot/user/remove', $user['.id']);
                $count++;
            }
        }

        return ['removed' => $count];
    }

    /**
     * =========================================================================
     * --- HOTSPOT ACTIVE SESSIONS ---
     * =========================================================================
     */

    public function getActive(): array
    {
        return $this->conn->read('/ip/hotspot/active/print');
    }

    public function getActiveUserDetails(string $user): array
    {
        return $this->conn->read('/ip/hotspot/active/print', ['user' => $user]);
    }

    public function getActiveUsersByServer(string $server): array
    {
        return $this->conn->read('/ip/hotspot/active/print', ['server' => $server]);
    }

    /**
     * Get real-time traffic & bandwidth consumption for a specific active user.
     *
     * @param string $username
     * @return array|null
     */
    public function getUserTraffic(string $username): ?array
    {
        $sessions = $this->getActiveUserDetails($username);
        if (empty($sessions) || isset($sessions['error'])) {
            return null;
        }

        $session = $sessions[0];
        $bytesIn = (float)($session['bytes-in'] ?? 0);
        $bytesOut = (float)($session['bytes-out'] ?? 0);

        return [
            'id'             => $session['.id'] ?? '',
            'user'           => $session['user'] ?? $username,
            'address'        => $session['address'] ?? '',
            'mac_address'    => $session['mac-address'] ?? '',
            'uptime'         => $session['uptime'] ?? '0s',
            'bytes_in'       => $bytesIn,
            'bytes_out'      => $bytesOut,
            'bytes_in_human' => Helper::formatBytes($bytesIn),
            'bytes_out_human'=> Helper::formatBytes($bytesOut),
            'total_bytes'    => $bytesIn + $bytesOut,
            'total_human'    => Helper::formatBytes($bytesIn + $bytesOut),
        ];
    }

    /**
     * Get top bandwidth downloaders/consumers sorted descending.
     *
     * @param int $limit
     * @return array
     */
    public function getTopDownloaders(int $limit = 10): array
    {
        $traffic = $this->getActiveTraffic();
        if (empty($traffic) || isset($traffic['error'])) {
            return [];
        }

        usort($traffic, function ($a, $b) {
            return $b['total_bytes'] <=> $a['total_bytes'];
        });

        return array_slice($traffic, 0, $limit);
    }

    /**
     * Get top active users by longest session uptime.
     *
     * @param int $limit
     * @return array
     */
    public function getTopActiveTime(int $limit = 10): array
    {
        $active = $this->getActive();
        if (empty($active) || isset($active['error'])) {
            return [];
        }

        // Sort by uptime descending
        usort($active, function ($a, $b) {
            return strcmp($b['uptime'] ?? '', $a['uptime'] ?? '');
        });

        return array_slice($active, 0, $limit);
    }

    /**
     * Extend user's uptime limit.
     *
     * @param string $name Username
     * @param string $additionalTime e.g. '1h', '2h', '1d'
     * @return array
     */
    public function extendUserTime(string $name, string $additionalTime): array
    {
        $user = $this->getUserDetails($name);
        if (empty($user) || isset($user['error'])) {
            return ['error' => 'User not found'];
        }

        $currentLimit = $user[0]['limit-uptime'] ?? '0s';
        $newLimit = $additionalTime; // Set new limit or extend
        return $this->setUser($name, null, $newLimit);
    }

    /**
     * Extend user's total data quota (bytes).
     *
     * @param string $name Username
     * @param string|int $additionalBytes e.g. '1G', '500M', or 1073741824
     * @return array
     */
    public function extendUserData(string $name, $additionalBytes): array
    {
        $user = $this->getUserDetails($name);
        if (empty($user) || isset($user['error'])) {
            return ['error' => 'User not found'];
        }

        $addBytes = Helper::parseBytes($additionalBytes);
        $currentLimit = isset($user[0]['limit-bytes-total']) ? (float)$user[0]['limit-bytes-total'] : 0;
        $newTotal = $currentLimit + $addBytes;

        return $this->setUser($name, null, null, null, null, (string)$newTotal);
    }

    /**
     * Lock a Hotspot user strictly to a specific MAC address (prevents voucher sharing).
     *
     * @param string $username
     * @param string $macAddress
     * @return array
     */
    public function lockUserToMac(string $username, string $macAddress): array
    {
        $cleanMac = Helper::formatMac($macAddress);
        $id = str_starts_with($username, '*') ? $username : ($this->conn->findIdByName('/ip/hotspot/user/print', $username) ?? $username);
        return $this->conn->set('/ip/hotspot/user/set', $id, ['mac-address' => $cleanMac]);
    }

    /**
     * Unlock user MAC address restriction.
     *
     * @param string $username
     * @return array
     */
    public function unlockUserMac(string $username): array
    {
        $id = str_starts_with($username, '*') ? $username : ($this->conn->findIdByName('/ip/hotspot/user/print', $username) ?? $username);
        return $this->conn->set('/ip/hotspot/user/set', $id, ['mac-address' => '00:00:00:00:00:00']);
    }

    /**
     * Get DHCP Leases assigned to Hotspot clients.
     *
     * @return array
     */
    public function getHotspotDhcpLeases(): array
    {
        return $this->conn->read('/ip/dhcp-server/lease/print', ['dynamic' => 'true']);
    }

    public function getActiveCount(): int
    {
        $active = $this->getActive();
        return (is_array($active) && !isset($active['error'])) ? count($active) : 0;
    }

    public function removeActive(string $id): array
    {
        return $this->conn->remove('/ip/hotspot/active/remove', $id);
    }

    public function disconnectUser(string $username): array
    {
        $sessions = $this->getActiveUserDetails($username);
        if (isset($sessions['error']) || empty($sessions)) {
            return ['disconnected' => 0, 'status' => 'User is not currently active'];
        }

        $count = 0;
        foreach ($sessions as $session) {
            if (isset($session['.id'])) {
                $this->removeActive($session['.id']);
                $count++;
            }
        }

        return ['disconnected' => $count, 'user' => $username];
    }

    /**
     * =========================================================================
     * --- HOTSPOT HOSTS ---
     * =========================================================================
     */

    public function getHosts(): array
    {
        return $this->conn->read('/ip/hotspot/host/print');
    }

    public function getHostDetails(string $macAddress): array
    {
        return $this->conn->read('/ip/hotspot/host/print', ['mac-address' => $macAddress]);
    }

    public function getAuthorizedHosts(): array
    {
        return $this->conn->read('/ip/hotspot/host/print', ['authorized' => 'true']);
    }

    public function getBypassedHosts(): array
    {
        return $this->conn->read('/ip/hotspot/host/print', ['bypassed' => 'true']);
    }

    public function removeHost(string $id): array
    {
        return $this->conn->remove('/ip/hotspot/host/remove', $id);
    }

    /**
     * =========================================================================
     * --- HOTSPOT COOKIES ---
     * =========================================================================
     */

    public function getCookies(): array
    {
        return $this->conn->read('/ip/hotspot/cookie/print');
    }

    public function getCookieByUser(string $user): array
    {
        return $this->conn->read('/ip/hotspot/cookie/print', ['user' => $user]);
    }

    public function removeCookie(string $id): array
    {
        return $this->conn->remove('/ip/hotspot/cookie/remove', $id);
    }

    public function clearCookies(): array
    {
        $cookies = $this->getCookies();
        if (isset($cookies['error']) || empty($cookies)) {
            return ['removed' => 0, 'status' => 'No cookies found'];
        }

        $count = 0;
        foreach ($cookies as $c) {
            if (isset($c['.id'])) {
                $this->removeCookie($c['.id']);
                $count++;
            }
        }
        return ['removed' => $count];
    }

    /**
     * =========================================================================
     * --- HOTSPOT IP BINDING ---
     * =========================================================================
     */

    public function getIpBindings(): array
    {
        return $this->conn->read('/ip/hotspot/ip-binding/print');
    }

    public function addIpBinding(
        string $macAddress,
        string $type = 'bypassed',
        string $address = '',
        string $toAddress = '',
        string $server = 'all',
        string $comment = ''
    ): array {
        $params = [
            'mac-address' => Helper::formatMac($macAddress),
            'type'        => $type,
        ];
        if (!empty($address)) $params['address'] = $address;
        if (!empty($toAddress)) $params['to-address'] = $toAddress;
        if (!empty($server) && $server !== 'all') $params['server'] = $server;
        if (!empty($comment)) $params['comment'] = $comment;

        return $this->conn->write('/ip/hotspot/ip-binding/add', $params);
    }

    public function setIpBinding(string $id, array $params): array
    {
        return $this->conn->set('/ip/hotspot/ip-binding/set', $id, $params);
    }

    public function removeIpBinding(string $id): array
    {
        return $this->conn->remove('/ip/hotspot/ip-binding/remove', $id);
    }

    public function enableIpBinding(string $id): array
    {
        return $this->conn->enable('/ip/hotspot/ip-binding/enable', $id);
    }

    public function disableIpBinding(string $id): array
    {
        return $this->conn->disable('/ip/hotspot/ip-binding/disable', $id);
    }

    public function bypassDevice(string $macAddress, ?string $address = null, string $comment = 'VIP / Bypassed'): array
    {
        return $this->addIpBinding($macAddress, 'bypassed', $address ?? '', '', 'all', $comment);
    }

    public function blockDevice(string $macAddress, string $comment = 'Blocked Device'): array
    {
        return $this->addIpBinding($macAddress, 'blocked', '', '', 'all', $comment);
    }

    /**
     * =========================================================================
     * --- HOTSPOT USER PROFILES ---
     * =========================================================================
     */

    public function getUserProfiles(): array
    {
        return $this->conn->read('/ip/hotspot/user/profile/print');
    }

    public function getUserProfileDetails(string $name): array
    {
        return $this->conn->read('/ip/hotspot/user/profile/print', ['name' => $name]);
    }

    public function addUserProfile(string $name, string $rateLimit, string $sharedUsers = '1'): array
    {
        return $this->conn->write('/ip/hotspot/user/profile/add', [
            'name'         => $name,
            'rate-limit'   => $rateLimit,
            'shared-users' => $sharedUsers,
        ]);
    }

    public function addUserProfileDetailed(string $name, array $params): array
    {
        $params['name'] = $name;
        return $this->conn->write('/ip/hotspot/user/profile/add', $params);
    }

    public function setUserProfile(string $name, array $params): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/ip/hotspot/user/profile/print', $name) ?? $name);
        return $this->conn->set('/ip/hotspot/user/profile/set', $id, $params);
    }

    public function removeUserProfile(string $name): array
    {
        $id = str_starts_with($name, '*') ? $name : ($this->conn->findIdByName('/ip/hotspot/user/profile/print', $name) ?? $name);
        return $this->conn->remove('/ip/hotspot/user/profile/remove', $id);
    }

    /**
     * Generate rock-solid on-login script for Hotspot user profile (Mikhmon Official Compatible).
     * Handles automatic expiry tracking from first login, MAC lock, and optional Telegram alert.
     * Compatible with both RouterOS v6.xx and v7.xx+.
     *
     * @param string $validity Duration string e.g. '1h', '3h', '1d', '7d', '30d'
     * @param array $options [
     *    'lock_mac'         => true,
     *    'track_expiry'     => true,
     *    'expiry_mode'      => 'remove'|'notice'|'remove_record',
     *    'price'            => 0,
     *    'telegram_token'   => string,
     *    'telegram_chat_id' => string,
     *    'custom_script'    => string,
     * ]
     * @return string
     */
    public function generateOnLoginScript(string $validity = '1d', array $options = []): string
    {
        $lockMac = $options['lock_mac'] ?? true;
        $trackExpiry = $options['track_expiry'] ?? true;
        $expiryMode = strtolower((string)($options['expiry_mode'] ?? 'remove'));
        $price = (float)($options['price'] ?? 0);
        $tgToken = $options['telegram_token'] ?? '';
        $tgChat = $options['telegram_chat_id'] ?? '';
        $custom = $options['custom_script'] ?? '';

        $script = [];
        $script[] = '# Project RouterOS Automated Hotspot On-Login Script (Mikhmon Style)';
        $script[] = ':local uName "$user";';
        $script[] = ':local uMac $"mac-address";';
        $script[] = ':local uIp "$address";';

        // 1. MAC Binding: Lock voucher to the first logged-in device
        if ($lockMac) {
            $script[] = ':if ([/ip hotspot user get [find name=$uName] mac-address] = "") do={';
            $script[] = '    /ip hotspot user set [find name=$uName] mac-address=$uMac;';
            $script[] = '}';
        }

        // 2. Expiry Tracking & Auto-Termination Scheduler
        if ($trackExpiry && !empty($validity)) {
            $script[] = ':local uComm [/ip hotspot user get [find name=$uName] comment];';
            $script[] = ':if ([:find $uComm "exp:"] = nil) do={';
            $script[] = '    :local cDate [/system clock get date];';
            $script[] = '    :local cTime [/system clock get time];';
            $script[] = '    /ip hotspot user set [find name=$uName] comment=("$uComm exp:" . $cDate . " " . $cTime);';

            if ($expiryMode === 'notice') {
                $cleanOnEvent = '/ip hotspot active remove [find user=\"$uName\"]; /ip hotspot user set [find name=\"$uName\"] comment=("$uComm exp:EXPIRED"); /system scheduler remove [find name=\"$uName\"];';
            } elseif ($expiryMode === 'remove_record') {
                $cleanOnEvent = ':log info ("Voucher " . $uName . " expired and recorded"); /ip hotspot active remove [find user=\"$uName\"]; /ip hotspot user remove [find name=\"$uName\"]; /system scheduler remove [find name=\"$uName\"];';
            } else {
                // Default: Remove
                $cleanOnEvent = '/ip hotspot active remove [find user=\"$uName\"]; /ip hotspot user remove [find name=\"$uName\"]; /system scheduler remove [find name=\"$uName\"];';
            }

            $script[] = '    /system scheduler add name=$uName start-date=$cDate start-time=$cTime interval="' . $validity . '" on-event="' . $cleanOnEvent . '" comment="Auto Expire ' . $validity . '";';
            $script[] = '}';
        }

        // 3. Telegram notification
        if (!empty($tgToken) && !empty($tgChat)) {
            $tgUrl = 'https://api.telegram.org/bot' . $tgToken . '/sendMessage?chat_id=' . $tgChat . '&text=User+$user+Online+IP+$address';
            $script[] = '/tool fetch url="' . $tgUrl . '" keep-result=no;';
        }

        // 4. Custom scripting hook
        if (!empty($custom)) {
            $script[] = trim($custom);
        }

        return implode("\n", $script);
    }

    /**
     * Setup Mikhmon-style official Hotspot profile with complete on-login script & auto scheduler.
     *
     * @param string $name Profile name (e.g. '1JAM-2MB', '1HARI-5MB')
     * @param string $rateLimit e.g. '1M/2M'
     * @param string $validity e.g. '1h', '3h', '1d', '30d'
     * @param int|float $price Voucher price
     * @param string $expiryMode 'remove', 'notice', 'remove_record'
     * @param array $options
     * @return array
     */
    public function setupMikhmonProfile(
        string $name,
        string $rateLimit,
        string $validity = '1d',
        $price = 0,
        string $expiryMode = 'remove',
        array $options = []
    ): array {
        $options['price'] = $price;
        $options['expiry_mode'] = $expiryMode;
        $res = $this->createVoucherProfile($name, $rateLimit, $validity, $options);

        // Also ensure background ghost session cleaner scheduler is installed
        $this->installExpiryCleanupScheduler(5);

        return $res;
    }

    /**
     * Create or update a complete, ISP-grade Voucher User Profile with automatic on-login expiry logic.
     *
     * @param string $name Profile name (e.g. '1JAM-2MB', '1HARI-5MB')
     * @param string $rateLimit Bandwidth limit e.g. '2M/5M' or '1024k/2048k'
     * @param string $validity Uptime or absolute validity e.g. '1h', '3h', '1d', '30d'
     * @param array $options Extra parameters ('shared_users', 'lock_mac', 'telegram_token', etc.)
     * @return array
     */
    public function createVoucherProfile(
        string $name,
        string $rateLimit,
        string $validity = '1d',
        array $options = []
    ): array {
        $onLogin = $this->generateOnLoginScript($validity, $options);

        $params = [
            'name'                => $name,
            'rate-limit'          => $rateLimit,
            'shared-users'        => (string)($options['shared_users'] ?? '1'),
            'status-autorefresh'  => '1m',
            'transparent-proxy'   => 'no',
            'on-login'            => $onLogin,
        ];

        if (!empty($options['address_pool'])) {
            $params['address-pool'] = $options['address_pool'];
        }

        $existing = $this->conn->read('/ip/hotspot/user/profile/print', ['name' => $name]);
        if (!empty($existing) && is_array($existing) && !isset($existing['error'])) {
            $id = $existing[0]['.id'] ?? $name;
            return $this->conn->set('/ip/hotspot/user/profile/set', $id, $params);
        }

        return $this->conn->write('/ip/hotspot/user/profile/add', $params);
    }

    /**
     * Install background scheduler in RouterOS to clean up orphaned active sessions and dead cookies.
     *
     * @param int $intervalMinutes Check interval in minutes (default 5m)
     * @return array
     */
    public function installExpiryCleanupScheduler(int $intervalMinutes = 5): array
    {
        $schedName = 'project-ros-cleaner';
        $existing = $this->conn->read('/system/scheduler/print', ['name' => $schedName]);

        $onEvent = ':foreach i in=[/ip hotspot active find] do={:local u [/ip hotspot active get $i user]; :if ([/ip hotspot user find name=$u] = nil) do={/ip hotspot active remove $i;}};';

        $params = [
            'name'      => $schedName,
            'interval'  => "{$intervalMinutes}m",
            'on-event'  => $onEvent,
            'comment'   => 'Project RouterOS Automated Ghost Session Cleanup',
        ];

        if (!empty($existing) && is_array($existing) && !isset($existing['error'])) {
            $id = $existing[0]['.id'] ?? $schedName;
            return $this->conn->set('/system/scheduler/set', $id, $params);
        }

        return $this->conn->write('/system/scheduler/add', $params);
    }

    /**
     * =========================================================================
     * --- HOTSPOT SERVERS & SERVER PROFILES ---
     * =========================================================================
     */

    public function getServers(): array
    {
        return $this->conn->read('/ip/hotspot/print');
    }

    public function getServerProfiles(): array
    {
        return $this->conn->read('/ip/hotspot/profile/print');
    }

    public function addServer(string $name, string $interface, string $profile = 'default', array $extra = []): array
    {
        $extra['name'] = $name;
        $extra['interface'] = $interface;
        $extra['profile'] = $profile;
        return $this->conn->write('/ip/hotspot/add', $extra);
    }

    public function addServerProfile(string $name, string $dnsName, string $loginBy = 'http-chap,cookie'): array
    {
        return $this->conn->write('/ip/hotspot/profile/add', [
            'name'     => $name,
            'dns-name' => $dnsName,
            'login-by' => $loginBy,
        ]);
    }

    /**
     * =========================================================================
     * --- HOTSPOT WALLED GARDEN ---
     * =========================================================================
     */

    public function getWalledGardenRules(): array
    {
        return $this->conn->read('/ip/hotspot/walled-garden/print');
    }

    public function addWalledGardenDomain(string $domain, string $action = 'allow', string $comment = ''): array
    {
        $params = [
            'dst-host' => $domain,
            'action'   => $action,
        ];
        if (!empty($comment)) $params['comment'] = $comment;
        return $this->conn->write('/ip/hotspot/walled-garden/add', $params);
    }

    public function removeWalledGardenRuleByHost(string $dstHost): array
    {
        $rules = $this->conn->read('/ip/hotspot/walled-garden/print', ['dst-host' => $dstHost]);
        if (isset($rules['error']) || empty($rules)) {
            return ['removed' => 0, 'status' => 'No rule matching dst-host'];
        }

        $count = 0;
        foreach ($rules as $r) {
            if (isset($r['.id'])) {
                $this->conn->remove('/ip/hotspot/walled-garden/remove', $r['.id']);
                $count++;
            }
        }
        return ['removed' => $count, 'dst-host' => $dstHost];
    }

    /**
     * =========================================================================
     * --- TRAFFIC & DASHBOARD SUMMARY ---
     * =========================================================================
     */

    public function getSummary(): array
    {
        $users = $this->getUsers();
        $active = $this->getActive();
        $hosts = $this->getHosts();
        $bindings = $this->getIpBindings();
        $servers = $this->getServers();
        $profiles = $this->getUserProfiles();
        $cookies = $this->getCookies();
        $walled = $this->getWalledGardenRules();

        $bypassedHosts = 0;
        if (is_array($hosts) && !isset($hosts['error'])) {
            foreach ($hosts as $h) {
                if (isset($h['bypassed']) && $h['bypassed'] === 'true') {
                    $bypassedHosts++;
                }
            }
        }

        $bypassedBindings = 0;
        $blockedBindings = 0;
        if (is_array($bindings) && !isset($bindings['error'])) {
            foreach ($bindings as $b) {
                if (isset($b['type'])) {
                    if ($b['type'] === 'bypassed') $bypassedBindings++;
                    if ($b['type'] === 'blocked') $blockedBindings++;
                }
            }
        }

        return [
            'total_users'       => is_array($users) && !isset($users['error']) ? count($users) : 0,
            'active_users'      => is_array($active) && !isset($active['error']) ? count($active) : 0,
            'total_hosts'       => is_array($hosts) && !isset($hosts['error']) ? count($hosts) : 0,
            'bypassed_hosts'    => $bypassedHosts,
            'total_bindings'    => is_array($bindings) && !isset($bindings['error']) ? count($bindings) : 0,
            'bypassed_bindings' => $bypassedBindings,
            'blocked_bindings'  => $blockedBindings,
            'total_servers'     => is_array($servers) && !isset($servers['error']) ? count($servers) : 0,
            'user_profiles'     => is_array($profiles) && !isset($profiles['error']) ? count($profiles) : 0,
            'active_cookies'    => is_array($cookies) && !isset($cookies['error']) ? count($cookies) : 0,
            'walled_garden'     => is_array($walled) && !isset($walled['error']) ? count($walled) : 0,
        ];
    }

    public function getActiveTraffic(): array
    {
        $active = $this->getActive();
        if (isset($active['error']) || !is_array($active)) {
            return $active;
        }

        $formatted = [];
        foreach ($active as $session) {
            $bytesIn = isset($session['bytes-in']) ? (float)$session['bytes-in'] : 0;
            $bytesOut = isset($session['bytes-out']) ? (float)$session['bytes-out'] : 0;

            $formatted[] = [
                'id'              => $session['.id'] ?? '',
                'server'          => $session['server'] ?? '',
                'user'            => $session['user'] ?? '',
                'address'         => $session['address'] ?? '',
                'mac_address'     => $session['mac-address'] ?? '',
                'uptime'          => $session['uptime'] ?? '0s',
                'bytes_in'        => $bytesIn,
                'bytes_out'       => $bytesOut,
                'bytes_in_human'  => Helper::formatBytes($bytesIn),
                'bytes_out_human' => Helper::formatBytes($bytesOut),
                'total_bytes'     => $bytesIn + $bytesOut,
                'total_human'     => Helper::formatBytes($bytesIn + $bytesOut),
                'session_time_left' => $session['session-time-left'] ?? '',
            ];
        }

        return $formatted;
    }

    /**
     * =========================================================================
     * --- DATABASE SYNCHRONIZATION ---
     * =========================================================================
     */

    /**
     * Sync users from MikroTik to local SQL database.
     *
     * @param int|null $routerId
     * @return array
     */
    public function syncToDatabase(?int $routerId = null): array
    {
        if (!$this->db) {
            return ['error' => 'Database layer is not initialized.'];
        }

        $users = $this->getUsers();
        if (isset($users['error']) || !is_array($users)) {
            return ['error' => $users['error'] ?? 'Unable to retrieve users from RouterOS.'];
        }

        $synced = 0;
        foreach ($users as $u) {
            $username = $u['name'] ?? '';
            if (!$username) continue;

            $existing = $this->db->find('hotspot_users', 'username = :u', ['u' => $username]);
            $userData = [
                'router_id'    => $routerId,
                'routeros_id'  => $u['.id'] ?? null,
                'username'     => $username,
                'password'     => $u['password'] ?? null,
                'profile'      => $u['profile'] ?? 'default',
                'uptime_limit' => $u['limit-uptime'] ?? null,
                'bytes_limit'  => $u['limit-bytes-total'] ?? null,
                'email'        => $u['email'] ?? null,
                'mac_address'  => $u['mac-address'] ?? null,
                'comment'      => $u['comment'] ?? null,
                'disabled'     => (isset($u['disabled']) && $u['disabled'] === 'true') ? 1 : 0,
                'updated_at'   => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $this->db->update('hotspot_users', $userData, 'id = :id', ['id' => $existing['id']]);
            } else {
                $userData['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('hotspot_users', $userData);
            }
            $synced++;
        }

        $this->db->log('Hotspot', 'Sync Users', null, "Synced {$synced} users to SQL DB", 'success', $routerId);
        return ['synced' => $synced, 'total_routeros' => count($users)];
    }
}
