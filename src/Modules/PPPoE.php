<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class PPPoE
 * Full PPPoE Server, Secret, Profile, Isolation, and Active session management module.
 */
class PPPoE
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
     * --- PPPoE SECRETS (USERS) ---
     * =========================================================================
     */

    public function getUsers(): array
    {
        return $this->conn->read('/ppp/secret/print');
    }

    public function getUserDetails(string $name): array
    {
        $res = $this->conn->read('/ppp/secret/print', ['name' => $name]);
        if (is_array($res) && !empty($res)) {
            return $res[0] ?? $res;
        }
        return [];
    }

    public function findNameToId(string $name): ?string
    {
        if (str_starts_with($name, '*')) return $name;
        return $this->conn->findIdByName('/ppp/secret/print', $name);
    }

    public function findname_toId(string $name): ?string
    {
        return $this->findNameToId($name);
    }

    public function addUser(
        string $name,
        string $password,
        string $service = 'pppoe',
        string $profile = 'default',
        string $localAddress = '',
        string $remoteAddress = '',
        string $comment = '',
        string $callerId = ''
    ): array {
        $params = [
            'name'     => $name,
            'password' => $password,
            'service'  => $service,
            'profile'  => $profile,
        ];
        if (!empty($localAddress)) $params['local-address'] = $localAddress;
        if (!empty($remoteAddress)) $params['remote-address'] = $remoteAddress;
        if (!empty($callerId)) $params['caller-id'] = $callerId;
        if (!empty($comment)) $params['comment'] = $comment;

        $res = $this->conn->write('/ppp/secret/add', $params);

        if ($this->db && !isset($res['error'])) {
            $this->db->insert('pppoe_users', [
                'username'       => $name,
                'password'       => $password,
                'service'        => $service,
                'profile'        => $profile,
                'caller_id'      => $callerId ?: null,
                'local_address'  => $localAddress ?: null,
                'remote_address' => $remoteAddress ?: null,
                'status'         => 'active',
                'comment'        => $comment ?: null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            $this->db->log('PPPoE', 'Add User', $name, "Service: {$service}", 'success');
        }

        return $res;
    }

    public function setUser(
        string $name,
        ?string $password = null,
        ?string $profile = null,
        ?string $comment = null,
        ?string $localAddress = null,
        ?string $remoteAddress = null,
        ?string $callerId = null
    ): array {
        $id = $this->findNameToId($name) ?? $name;

        $params = [];
        if ($password !== null) $params['password'] = $password;
        if ($profile !== null) $params['profile'] = $profile;
        if ($comment !== null) $params['comment'] = $comment;
        if ($localAddress !== null) $params['local-address'] = $localAddress;
        if ($remoteAddress !== null) $params['remote-address'] = $remoteAddress;
        if ($callerId !== null) $params['caller-id'] = $callerId;

        $res = $this->conn->set('/ppp/secret/set', $id, $params);

        if ($this->db && !isset($res['error'])) {
            $dbParams = [];
            if ($password !== null) $dbParams['password'] = $password;
            if ($profile !== null) $dbParams['profile'] = $profile;
            if ($comment !== null) $dbParams['comment'] = $comment;
            if ($localAddress !== null) $dbParams['local_address'] = $localAddress;
            if ($remoteAddress !== null) $dbParams['remote_address'] = $remoteAddress;
            if ($callerId !== null) $dbParams['caller_id'] = $callerId;
            if (!empty($dbParams)) {
                $this->db->update('pppoe_users', $dbParams, 'username = :u', ['u' => $name]);
            }
        }

        return $res;
    }

    public function setComment(string $name, string $comment): array
    {
        return $this->setUser($name, null, null, $comment);
    }

    public function setcoment(string $name, string $comment): array
    {
        return $this->setComment($name, $comment);
    }

    public function setPassword(string $name, string $password): array
    {
        return $this->setUser($name, $password);
    }

    public function setProfile(string $name, string $profile): array
    {
        return $this->setUser($name, null, $profile);
    }

    public function setName(string $idOrOldName, string $newName): array
    {
        $id = $this->findNameToId($idOrOldName) ?? $idOrOldName;
        $res = $this->conn->set('/ppp/secret/set', $id, ['name' => $newName]);
        if ($this->db && !isset($res['error'])) {
            $this->db->update('pppoe_users', ['username' => $newName], 'username = :u', ['u' => $idOrOldName]);
        }
        return $res;
    }

    public function enableUser(string $name): array
    {
        $id = $this->findNameToId($name) ?? $name;
        $res = $this->conn->enable('/ppp/secret/enable', $id);

        if ($this->db && !isset($res['error'])) {
            $this->db->update('pppoe_users', ['status' => 'active'], 'username = :u', ['u' => $name]);
            $this->db->log('PPPoE', 'Enable User', $name, "User {$name} enabled", 'success');
        }

        return $res;
    }

    public function disableUser(string $name): array
    {
        $id = $this->findNameToId($name) ?? $name;
        $res = $this->conn->disable('/ppp/secret/disable', $id);
        $this->disconnectUser($name);

        if ($this->db && !isset($res['error'])) {
            $this->db->update('pppoe_users', ['status' => 'disabled'], 'username = :u', ['u' => $name]);
            $this->db->log('PPPoE', 'Disable User', $name, "User {$name} disabled", 'success');
        }

        return $res;
    }

    public function removeUser(string $name): array
    {
        $id = $this->findNameToId($name) ?? $name;
        $res = $this->conn->remove('/ppp/secret/remove', $id);

        if ($this->db && !isset($res['error'])) {
            $this->db->delete('pppoe_users', 'username = :u', ['u' => $name]);
            $this->db->log('PPPoE', 'Remove User', $name, "Removed PPPoE user {$name}", 'success');
        }

        return $res;
    }

    /**
     * Bind a PPPoE secret to a specific MAC/Calling-Station-ID.
     */
    public function bindCallerId(string $username, string $macAddress): array
    {
        $id = $this->findNameToId($username) ?? $username;
        $res = $this->conn->set('/ppp/secret/set', $id, ['caller-id' => $macAddress]);

        if ($this->db && !isset($res['error'])) {
            $this->db->update('pppoe_users', ['caller_id' => $macAddress], 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Bind Caller ID', $username, "Bound to MAC: {$macAddress}", 'success');
        }

        return $res;
    }

    /**
     * Remove MAC/Calling-Station-ID restriction from a PPPoE secret.
     */
    public function unbindCallerId(string $username): array
    {
        $id = $this->findNameToId($username) ?? $username;
        $res = $this->conn->set('/ppp/secret/set', $id, ['caller-id' => '']);

        if ($this->db && !isset($res['error'])) {
            $this->db->update('pppoe_users', ['caller_id' => null], 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Unbind Caller ID', $username, "Unbound caller ID lock", 'success');
        }

        return $res;
    }

    /**
     * Assign a fixed/static IP address to a PPPoE secret.
     */
    public function assignStaticIp(string $username, string $remoteAddress, string $localAddress = ''): array
    {
        $id = $this->findNameToId($username) ?? $username;
        $params = ['remote-address' => $remoteAddress];
        if (!empty($localAddress)) {
            $params['local-address'] = $localAddress;
        }

        $res = $this->conn->set('/ppp/secret/set', $id, $params);

        if ($this->db && !isset($res['error'])) {
            $update = ['remote_address' => $remoteAddress];
            if (!empty($localAddress)) {
                $update['local_address'] = $localAddress;
            }
            $this->db->update('pppoe_users', $update, 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Assign Static IP', $username, "Remote IP: {$remoteAddress}", 'success');
        }

        return $res;
    }

    /**
     * =========================================================================
     * --- SUSPEND / ISOLIR & BILLING DUE DATE ---
     * =========================================================================
     */

    public function suspendCustomer(string $username, string $isolateProfile = 'ISOLIR'): array
    {
        $id = $this->findNameToId($username) ?? $username;
        $res = $this->conn->set('/ppp/secret/set', $id, ['profile' => $isolateProfile]);

        if (isset($res['error'])) {
            return ['success' => false, 'error' => $res['error']];
        }

        // Immediately drop active session so reconnection lands on isolation pool
        $disconnect = $this->disconnectUser($username);

        if ($this->db) {
            $this->db->update('pppoe_users', [
                'profile' => $isolateProfile,
                'status'  => 'suspended'
            ], 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Suspend Customer', $username, "Switched profile to {$isolateProfile}", 'success');
        }

        return [
            'success'              => true,
            'username'             => $username,
            'status'               => 'suspended',
            'isolate_profile'      => $isolateProfile,
            'session_disconnected' => $disconnect['disconnected'] ?? 0,
        ];
    }

    public function unsuspendCustomer(string $username, string $activeProfile = 'default'): array
    {
        $id = $this->findNameToId($username) ?? $username;
        $res = $this->conn->set('/ppp/secret/set', $id, ['profile' => $activeProfile]);

        if (isset($res['error'])) {
            return ['success' => false, 'error' => $res['error']];
        }

        // Kick session to re-establish on standard pool
        $disconnect = $this->disconnectUser($username);

        if ($this->db) {
            $this->db->update('pppoe_users', [
                'profile' => $activeProfile,
                'status'  => 'active'
            ], 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Unsuspend Customer', $username, "Reactivated with profile {$activeProfile}", 'success');
        }

        return [
            'success'              => true,
            'username'             => $username,
            'status'               => 'active',
            'active_profile'       => $activeProfile,
            'session_disconnected' => $disconnect['disconnected'] ?? 0,
        ];
    }

    /**
     * Reconnect and reactivate an isolated customer upon payment (Mikhmon & ISP Billing standard).
     * Restores subscribed profile, updates due date in comment, kicks active session, and logs event.
     *
     * @param string $username PPPoE username
     * @param string $targetProfile Target package profile (e.g. '10MBPS')
     * @param string|null $nextDueDate New expiration/due date (YYYY-MM-DD)
     * @param string|null $comment Additional billing note / invoice number
     * @return array
     */
    public function reconnectCustomer(
        string $username,
        string $targetProfile = 'default',
        ?string $nextDueDate = null,
        ?string $comment = null
    ): array {
        $id = $this->findNameToId($username) ?? $username;
        $userDetails = $this->getUserDetails($username);
        $oldComment = $userDetails['comment'] ?? '';

        // Clean previous ISOLATED tag and construct new comment
        $cleanComment = preg_replace('/\[ISOLATED[^\]]*\]\s*/i', '', $oldComment);
        if ($nextDueDate) {
            if (preg_match('/due:[^\s\|]+/i', $cleanComment)) {
                $cleanComment = preg_replace('/due:[^\s\|]+/i', "due:{$nextDueDate}", $cleanComment);
            } else {
                $cleanComment = trim($cleanComment . " due:{$nextDueDate}");
            }
        }
        if ($comment) {
            $cleanComment = trim($cleanComment . " | {$comment}");
        }
        $cleanComment = trim($cleanComment);

        $params = ['profile' => $targetProfile];
        if (!empty($cleanComment)) {
            $params['comment'] = $cleanComment;
        }

        $res = $this->conn->set('/ppp/secret/set', $id, $params);
        if (isset($res['error'])) {
            return ['success' => false, 'error' => $res['error']];
        }

        // Kick active session so customer reconnects immediately with high-speed profile
        $disconnect = $this->disconnectUser($username);

        if ($this->db) {
            $dbParams = [
                'profile'  => $targetProfile,
                'status'   => 'active',
                'comment'  => $cleanComment ?: null,
            ];
            if ($nextDueDate) {
                $dbParams['due_date'] = $nextDueDate;
            }
            $this->db->update('pppoe_users', $dbParams, 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Reconnect Customer', $username, "Reactivated to profile {$targetProfile}" . ($nextDueDate ? " (Next Due: {$nextDueDate})" : ""), 'success');
        }

        return [
            'success'              => true,
            'username'             => $username,
            'status'               => 'active',
            'profile'              => $targetProfile,
            'due_date'             => $nextDueDate,
            'comment'              => $cleanComment,
            'session_disconnected' => $disconnect['disconnected'] ?? 0,
        ];
    }

    /**
     * Isolate a customer immediately (switch profile to ISOLIR and terminate active session).
     *
     * @param string $username PPPoE username
     * @param string $isolateProfile Isolation profile name (default 'ISOLIR')
     * @param string $reason Reason for isolation
     * @return array
     */
    public function isolateCustomer(string $username, string $isolateProfile = 'ISOLIR', string $reason = 'Jatuh Tempo'): array
    {
        $id = $this->findNameToId($username) ?? $username;
        $userDetails = $this->getUserDetails($username);
        $oldComment = $userDetails['comment'] ?? '';

        $newComment = trim($oldComment . " [ISOLATED: {$reason}]");
        $res = $this->conn->set('/ppp/secret/set', $id, [
            'profile' => $isolateProfile,
            'comment' => $newComment,
        ]);

        if (isset($res['error'])) {
            return ['success' => false, 'error' => $res['error']];
        }

        $disconnect = $this->disconnectUser($username);

        if ($this->db) {
            $this->db->update('pppoe_users', [
                'profile' => $isolateProfile,
                'status'  => 'suspended',
                'comment' => $newComment,
            ], 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Isolate Customer', $username, "Isolated: {$reason}", 'warning');
        }

        return [
            'success'              => true,
            'username'             => $username,
            'status'               => 'suspended',
            'isolate_profile'      => $isolateProfile,
            'reason'               => $reason,
            'session_disconnected' => $disconnect['disconnected'] ?? 0,
        ];
    }

    public function setPppoeDueDate(string $username, string $dueDate, ?string $invoiceId = null): array
    {
        $id = $this->findNameToId($username) ?? $username;
        $commentTag = "due:{$dueDate}" . ($invoiceId ? " | inv:{$invoiceId}" : "");
        $res = $this->conn->set('/ppp/secret/set', $id, ['comment' => $commentTag]);

        if ($this->db && !isset($res['error'])) {
            $this->db->update('pppoe_users', [
                'due_date' => $dueDate,
                'comment'  => $commentTag
            ], 'username = :u', ['u' => $username]);
            $this->db->log('PPPoE', 'Set Due Date', $username, "Due: {$dueDate}", 'success');
        }

        return [
            'success'  => !isset($res['error']),
            'username' => $username,
            'due_date' => $dueDate,
            'comment'  => $commentTag,
        ];
    }

    public function getOverdueCustomers(string $currentDate = ''): array
    {
        $today = !empty($currentDate) ? $currentDate : date('Y-m-d');
        $overdue = [];

        if ($this->db) {
            $rows = $this->db->fetchAll(
                "SELECT * FROM pppoe_users WHERE due_date IS NOT NULL AND due_date < :today AND (status IS NULL OR status != 'suspended')",
                ['today' => $today]
            );
            return $rows ?: [];
        }

        // Fallback: scan RouterOS secret comments
        $users = $this->getUsers();
        if (is_array($users) && !isset($users['error'])) {
            foreach ($users as $u) {
                $comment = $u['comment'] ?? '';
                if (preg_match('/due:([0-9]{4}-[0-9]{2}-[0-9]{2})/', $comment, $matches)) {
                    $userDue = $matches[1];
                    if ($userDue < $today) {
                        $overdue[] = [
                            'username' => $u['name'] ?? '',
                            'profile'  => $u['profile'] ?? 'default',
                            'due_date' => $userDue,
                            'comment'  => $comment,
                        ];
                    }
                }
            }
        }

        return $overdue;
    }

    public function autoIsolateOverdue(string $isolateProfile = 'ISOLIR', string $currentDate = ''): array
    {
        $overdues = $this->getOverdueCustomers($currentDate);
        $isolated = [];

        foreach ($overdues as $customer) {
            $username = $customer['username'] ?? '';
            if (!$username) continue;

            $res = $this->suspendCustomer($username, $isolateProfile);
            if (!empty($res['success'])) {
                $isolated[] = $username;
            }
        }

        return [
            'total_overdue'   => count($overdues),
            'total_isolated'  => count($isolated),
            'isolated_users'  => $isolated,
            'isolate_profile' => $isolateProfile,
        ];
    }

    /**
     * =========================================================================
     * --- ACTIVE PPPoE SESSIONS & MONITORING ---
     * =========================================================================
     */

    public function getActive(): array
    {
        return $this->conn->read('/ppp/active/print');
    }

    public function removeActive(string $id): array
    {
        return $this->conn->remove('/ppp/active/remove', $id);
    }

    public function disconnectUser(string $username): array
    {
        $sessions = $this->conn->read('/ppp/active/print', ['name' => $username]);
        if (isset($sessions['error']) || empty($sessions)) {
            return ['disconnected' => 0, 'status' => 'User not active'];
        }

        $count = 0;
        foreach ($sessions as $s) {
            if (isset($s['.id'])) {
                $this->removeActive($s['.id']);
                $count++;
            }
        }
        return ['disconnected' => $count, 'name' => $username];
    }

    public function getUserTraffic(string $username): ?array
    {
        $sessions = $this->conn->read('/ppp/active/print', ['name' => $username]);
        if (empty($sessions) || isset($sessions['error'])) {
            return null;
        }
        $session = $sessions[0] ?? $sessions;

        $rxBytes = 0;
        $txBytes = 0;
        $interfaceName = "<pppoe-{$username}>";
        $iface = $this->conn->read('/interface/print', ['name' => $interfaceName]);
        if (!empty($iface) && !isset($iface['error'])) {
            $rxBytes = (int)($iface[0]['rx-byte'] ?? 0);
            $txBytes = (int)($iface[0]['tx-byte'] ?? 0);
        }

        return [
            'username'     => $username,
            'is_active'    => true,
            'service'      => $session['service'] ?? 'pppoe',
            'caller_id'    => $session['caller-id'] ?? '',
            'address'      => $session['address'] ?? '',
            'uptime'       => $session['uptime'] ?? '0s',
            'rx_bytes'     => $rxBytes,
            'tx_bytes'     => $txBytes,
            'total_bytes'  => $rxBytes + $txBytes,
            'rx_human'     => Helper::formatBytes($rxBytes),
            'tx_human'     => Helper::formatBytes($txBytes),
            'total_human'  => Helper::formatBytes($rxBytes + $txBytes),
            'session_id'   => $session['.id'] ?? null,
        ];
    }

    public function getTopActiveTime(int $limit = 10): array
    {
        $actives = $this->getActive();
        if (isset($actives['error']) || !is_array($actives)) {
            return [];
        }

        usort($actives, function ($a, $b) {
            $uptimeA = Helper::timeToSeconds($a['uptime'] ?? '0s');
            $uptimeB = Helper::timeToSeconds($b['uptime'] ?? '0s');
            return $uptimeB <=> $uptimeA;
        });

        return array_slice($actives, 0, $limit);
    }

    /**
     * =========================================================================
     * --- PPPoE SERVERS & PROFILES ---
     * =========================================================================
     */

    public function getServers(): array
    {
        return $this->conn->read('/interface/pppoe-server/print');
    }

    public function addServer(string $name, string $interface, string $serviceName = 'pppoeService'): array
    {
        return $this->conn->write('/interface/pppoe-server/add', [
            'name'         => $name,
            'interface'    => $interface,
            'service-name' => $serviceName,
        ]);
    }

    public function removeServer(string $name): array
    {
        $id = $this->findNameToId($name) ?? $name;
        return $this->conn->remove('/interface/pppoe-server/remove', $id);
    }

    public function getProfiles(): array
    {
        return $this->conn->read('/ppp/profile/print');
    }

    public function getProfileDetails(string $name): array
    {
        $res = $this->conn->read('/ppp/profile/print', ['name' => $name]);
        return (!empty($res) && !isset($res['error'])) ? ($res[0] ?? $res) : [];
    }

    public function addProfile(
        string $name,
        string $localAddress,
        string $remoteAddress,
        string $rateLimit = ''
    ): array {
        $params = [
            'name'           => $name,
            'local-address'  => $localAddress,
            'remote-address' => $remoteAddress,
        ];
        if (!empty($rateLimit)) $params['rate-limit'] = $rateLimit;

        return $this->conn->write('/ppp/profile/add', $params);
    }

    public function removeProfile(string $name): array
    {
        $id = $this->findNameToId($name) ?? $name;
        return $this->conn->remove('/ppp/profile/remove', $id);
    }

    public function ensureIsolateProfile(
        string $name = 'ISOLIR',
        string $localAddress = '10.0.99.1',
        string $remoteAddress = '10.0.99.2-10.0.99.254',
        string $rateLimit = '128k/128k'
    ): array {
        $existing = $this->conn->read('/ppp/profile/print', ['name' => $name]);
        if (!empty($existing) && !isset($existing['error'])) {
            return ['status' => 'exists', 'profile' => $existing[0] ?? $existing];
        }

        $res = $this->addProfile($name, $localAddress, $remoteAddress, $rateLimit);
        return ['status' => 'created', 'profile' => $name, 'result' => $res];
    }

    /**
     * Generate authentic PPP Profile on-up script.
     * When customer connects and secret comment starts with 'lunas', calculates expiry and sets comment.
     *
     * @param string $validity Expiration interval (e.g. '30d', '7d', '1d')
     * @return string
     */
    public function generatePppOnUpScript(string $validity = '30d'): string
    {
        return \RouterOSFramework\Core\AutomationEngine::getPppOnUpScript($validity);
    }

    /**
     * Backward compatibility alias for generatePppOnUpScript.
     */
    public function generateMikhmonPppOnUpScript(string $validity = '30d'): string
    {
        return $this->generatePppOnUpScript($validity);
    }

    /**
     * Generate authentic PPPoE Isolir scheduler script.
     * Periodically monitors /ppp secret comments and shifts overdue users to profile=isolir while dropping active connection.
     *
     * @return string
     */
    public function generateIsolirSchedulerScript(): string
    {
        return \RouterOSFramework\Core\AutomationEngine::getIsolirSchedulerScript();
    }

    /**
     * Backward compatibility alias for generateIsolirSchedulerScript.
     */
    public function generateMikhmonPppoeIsolirSchedulerScript(): string
    {
        return $this->generateIsolirSchedulerScript();
    }

    /**
     * Install authentic PPPoE Isolir scheduler on RouterOS:
     * add name="isolir" start-time=startup interval="01:00:00" comment="Do not remove!!! Monitor PPP Secret Isolir Report"
     *
     * @param string $interval Default '01:00:00'
     * @return array
     */
    public function installIsolirScheduler(string $interval = '01:00:00'): array
    {
        $system = new System($this->conn, $this->db);
        $sched = \RouterOSFramework\Core\AutomationEngine::getIsolirScheduler();
        return $system->ensureScheduler(
            $sched['name'],
            $sched['on-event'],
            $interval,
            $sched['start-time'] ?? 'startup',
            '',
            $sched['comment'] ?? ''
        );
    }

    /**
     * Backward compatibility alias for installIsolirScheduler.
     */
    public function installMikhmonPppoeIsolirScheduler(string $interval = '01:00:00'): array
    {
        return $this->installIsolirScheduler($interval);
    }

    /**
     * Install complete Isolir infrastructure:
     * 1. IP Pool 'isolir' (default 10.10.10.2-10.10.10.254)
     * 2. PPP Profile 'isolir' (512k/512k, local 10.10.10.255, remote 'isolir', dns '8.8.8.8,8.8.4.4')
     * 3. System scheduler 'isolir' (hourly check with auto-disconnect)
     *
     * @param string $poolRanges IP range for isolated pool (default '10.10.10.2-10.10.10.254')
     * @param string $localAddress Local gateway IP for isolated clients (default '10.10.10.255')
     * @param string $rateLimit Bandwidth throttling for isolated clients (default '512k/512k')
     * @param string $dnsServer DNS server for isolated clients (default '8.8.8.8,8.8.4.4')
     * @return array
     */
    public function installIsolirInfrastructure(
        string $poolRanges = '10.10.10.2-10.10.10.254',
        string $localAddress = '10.10.10.255',
        string $rateLimit = '512k/512k',
        string $dnsServer = '8.8.8.8,8.8.4.4'
    ): array {
        // 1. IP Pool isolir
        $ip = new Ip($this->conn, $this->db);
        $poolRes = $ip->ensurePool('isolir', $poolRanges);

        // 2. PPP Profile isolir
        $profileParams = [
            'name'           => 'isolir',
            'rate-limit'     => $rateLimit,
            'local-address'  => $localAddress,
            'remote-address' => 'isolir',
            'dns-server'     => $dnsServer,
            'comment'        => 'Profile untuk pelanggan yang diisolir',
        ];
        $existing = $this->conn->read('/ppp/profile/print', ['name' => 'isolir']);
        if (!empty($existing) && is_array($existing) && !isset($existing['error'])) {
            $id = $existing[0]['.id'] ?? 'isolir';
            $profRes = $this->conn->set('/ppp/profile/set', $id, $profileParams);
        } else {
            $profRes = $this->conn->write('/ppp/profile/add', $profileParams);
        }

        // 3. System scheduler isolir
        $schedRes = $this->installIsolirScheduler();

        return [
            'status'         => 'success',
            'pool_result'    => $poolRes,
            'profile_result' => $profRes,
            'sched_result'   => $schedRes,
        ];
    }

    /**
     * Backward compatibility alias for installIsolirInfrastructure.
     */
    public function installMikhmonIsolirInfrastructure(
        string $poolRanges = '10.10.10.2-10.10.10.254',
        string $localAddress = '10.10.10.255',
        string $rateLimit = '512k/512k',
        string $dnsServer = '8.8.8.8,8.8.4.4'
    ): array {
        return $this->installIsolirInfrastructure($poolRanges, $localAddress, $rateLimit, $dnsServer);
    }

    /**
     * Setup authentic Customer PPP Profile (e.g. 'P3').
     * Automatically ensures IP pool 'isolir', profile 'isolir', and scheduler 'isolir' exist.
     * Injects authentic on-up script for automatic expiration calculation upon login when comment has 'lunas'.
     *
     * @param string $name Profile name (e.g. 'P3', 'Paket-Home-10M')
     * @param string $rateLimit Bandwidth limit (e.g. '3M/3M', '10M/10M')
     * @param string $localAddress Gateway IP (default '192.168.100.1')
     * @param string $remoteAddress Pool or IP (default 'isolir')
     * @param string $validity Expiration interval (default '30d')
     * @param array $options Extra parameters ('parent_queue', 'dns_server', 'auto_isolir', 'pool_ranges')
     * @return array
     */
    public function setupCustomerProfile(
        string $name = 'P3',
        string $rateLimit = '3M/3M',
        string $localAddress = '192.168.100.1',
        string $remoteAddress = 'isolir',
        string $validity = '30d',
        array $options = []
    ): array {
        // 1. Ensure isolir infrastructure unless disabled
        $autoIsolir = $options['auto_isolir'] ?? true;
        $isolirInfra = null;
        if ($autoIsolir) {
            $isolirInfra = $this->installIsolirInfrastructure();
        }

        // 2. If custom remote pool specified and pool_ranges given, ensure pool
        if (!empty($remoteAddress) && $remoteAddress !== 'none' && $remoteAddress !== 'isolir' && !empty($options['pool_ranges'])) {
            $ip = new Ip($this->conn, $this->db);
            $ip->ensurePool($remoteAddress, $options['pool_ranges']);
        }

        // 3. Generate authentic on-up script
        $onUp = $this->generatePppOnUpScript($validity);

        // 4. Create or update customer profile
        $params = [
            'name'           => $name,
            'rate-limit'     => $rateLimit,
            'local-address'  => $localAddress,
            'remote-address' => $remoteAddress,
            'dns-server'     => $options['dns_server'] ?? '8.8.8.8,8.8.4.4',
            'parent-queue'   => (!empty($options['parent_queue']) && $options['parent_queue'] !== 'none') ? $options['parent_queue'] : 'none',
            'on-up'          => $onUp,
        ];

        $existing = $this->conn->read('/ppp/profile/print', ['name' => $name]);
        if (!empty($existing) && is_array($existing) && !isset($existing['error'])) {
            $id = $existing[0]['.id'] ?? $name;
            $res = $this->conn->set('/ppp/profile/set', $id, $params);
        } else {
            $res = $this->conn->write('/ppp/profile/add', $params);
        }

        return [
            'status'         => !isset($res['error']) ? 'success' : 'error',
            'profile'        => $name,
            'rate_limit'     => $rateLimit,
            'local_address'  => $localAddress,
            'remote_address' => $remoteAddress,
            'validity'       => $validity,
            'result'         => $res,
            'isolir_infra'   => $isolirInfra,
        ];
    }

    /**
     * Backward compatibility alias for setupCustomerProfile.
     */
    public function setupMikhmonCustomerProfile(
        string $name = 'P3',
        string $rateLimit = '3M/3M',
        string $localAddress = '192.168.100.1',
        string $remoteAddress = 'isolir',
        string $validity = '30d',
        array $options = []
    ): array {
        return $this->setupCustomerProfile($name, $rateLimit, $localAddress, $remoteAddress, $validity, $options);
    }

    /**
     * Generate complete RouterOS CLI script commands for PPP configuration.
     * Includes /ip pool, /ppp profiles (isolir & customer), and /system scheduler isolir.
     *
     * @param string $customerProfile Customer profile name (default 'P3')
     * @param string $rateLimit Customer rate limit (default '3M/3M')
     * @param string $localAddress Customer local IP (default '192.168.100.1')
     * @param string $remoteAddress Remote pool or IP (default 'isolir')
     * @param string $validity Customer validity interval (default '30d')
     * @param array $options Extra parameters
     * @return string
     */
    public function generatePppCliCommands(
        string $customerProfile = 'P3',
        string $rateLimit = '3M/3M',
        string $localAddress = '192.168.100.1',
        string $remoteAddress = 'isolir',
        string $validity = '30d',
        array $options = []
    ): string {
        $parentQueue = $options['parent_queue'] ?? 'none';
        $dnsServer = $options['dns_server'] ?? '8.8.8.8,8.8.4.4';
        $isolirPoolRange = $options['isolir_pool_range'] ?? '10.10.10.2-10.10.10.254';
        $isolirLocalIp = $options['isolir_local_ip'] ?? '10.10.10.255';
        $isolirRate = $options['isolir_rate'] ?? '512k/512k';

        $onUp = $this->generatePppOnUpScript($validity);
        $escapedOnUp = str_replace('"', '\"', $onUp);

        $schedScript = $this->generateIsolirSchedulerScript();
        $escapedSched = str_replace('"', '\"', $schedScript);

        $cmd = "/ppp profiles\n";
        $cmd .= "add name=\"{$customerProfile}\" parent-queue=\"{$parentQueue}\" rate-limit=\"{$rateLimit}\" local-address=\"{$localAddress}\" remote-address=\"{$remoteAddress}\" dns-server=\"{$dnsServer}\" on-up=\"{$escapedOnUp}\"\n";
        $cmd .= "add name=\"isolir\" rate-limit=\"{$isolirRate}\" local-address=\"{$isolirLocalIp}\" comment=\"Profile untuk pelanggan yang diisolir\" dns-server=\"{$dnsServer}\" remote-address=\"isolir\"\n\n";

        $cmd .= "/ip pool\n";
        $cmd .= "add name=\"isolir\" address=\"{$isolirPoolRange}\"\n";
        if ($remoteAddress !== 'none' && $remoteAddress !== 'isolir' && !empty($options['pool_ranges'])) {
            $cmd .= "add name=\"{$remoteAddress}\" address=\"{$options['pool_ranges']}\"\n";
        }

        $cmd .= "\n/system scheduler\n";
        $cmd .= "add name=\"isolir\" start-time=startup interval=\"01:00:00\" comment=\"Do not remove!!! Monitor PPP Secret Isolir Report\" on-event=\"{$escapedSched}\"\n";

        return $cmd;
    }

    /**
     * Backward compatibility alias for generatePppCliCommands.
     */
    public function generateMikhmonPppCliCommands(
        string $customerProfile = 'P3',
        string $rateLimit = '3M/3M',
        string $localAddress = '192.168.100.1',
        string $remoteAddress = 'isolir',
        string $validity = '30d',
        array $options = []
    ): string {
        return $this->generatePppCliCommands($customerProfile, $rateLimit, $localAddress, $remoteAddress, $validity, $options);
    }

    /**
     * Generate RouterOS auto-isolation script for PPPoE secrets (Backward compatibility).
     *
     * @param string $isolateProfile
     * @return string
     */
    public function generatePppoeIsolirScript(string $isolateProfile = 'ISOLIR'): string
    {
        return $this->generateIsolirSchedulerScript();
    }

    /**
     * Install background scheduler in RouterOS for automated PPPoE customer isolation (Backward compatibility).
     *
     * @param string $isolateProfile Isolation profile name
     * @param string $interval Check interval (default '1h', or '01:00:00')
     * @return array
     */
    public function installPppoeIsolirScheduler(string $isolateProfile = 'ISOLIR', string $interval = '1h'): array
    {
        if ($interval === '1h') {
            $interval = '01:00:00';
        }
        return $this->installIsolirScheduler($interval);
    }

    /**
     * Setup official PPPoE Isolir Profile with pool, rate-limit, and automated background scheduler.
     *
     * @param string $name Profile name (default 'isolir')
     * @param array $options [
     *   'pool_ranges'        => '10.10.10.2-10.10.10.254',
     *   'local_address'      => '10.10.10.255',
     *   'rate_limit'         => '512k/512k',
     *   'dns_server'         => '8.8.8.8,8.8.4.4',
     *   'install_scheduler'  => true,
     * ]
     * @return array
     */
    public function setupIsolirProfile(string $name = 'isolir', array $options = []): array
    {
        $poolRanges = $options['pool_ranges'] ?? '10.10.10.2-10.10.10.254';
        $localAddress = $options['local_address'] ?? '10.10.10.255';
        $rateLimit = $options['rate_limit'] ?? '512k/512k';
        $dnsServer = $options['dns_server'] ?? '8.8.8.8,8.8.4.4';

        return $this->installIsolirInfrastructure($poolRanges, $localAddress, $rateLimit, $dnsServer);
    }

    /**
     * Backward compatibility alias for setupIsolirProfile.
     */
    public function setupMikhmonIsolirProfile(string $name = 'isolir', array $options = []): array
    {
        return $this->setupIsolirProfile($name, $options);
    }

    /**
     * =========================================================================
     * --- DATABASE SYNCHRONIZATION ---
     * =========================================================================
     */

    public function syncToDatabase(?int $routerId = null): array
    {
        if (!$this->db) {
            return ['error' => 'Database layer is not initialized.'];
        }

        $users = $this->getUsers();
        if (isset($users['error']) || !is_array($users)) {
            return ['error' => $users['error'] ?? 'Unable to retrieve PPPoE users from RouterOS.'];
        }

        $synced = 0;
        foreach ($users as $u) {
            $username = $u['name'] ?? '';
            if (!$username) continue;

            $existing = $this->db->find('pppoe_users', 'username = :u', ['u' => $username]);
            $userData = [
                'router_id'      => $routerId,
                'username'       => $username,
                'password'       => $u['password'] ?? '',
                'service'        => $u['service'] ?? 'pppoe',
                'profile'        => $u['profile'] ?? 'default',
                'caller_id'      => $u['caller-id'] ?? null,
                'local_address'  => $u['local-address'] ?? null,
                'remote_address' => $u['remote-address'] ?? null,
                'status'         => ($u['disabled'] ?? 'false') === 'true' ? 'disabled' : 'active',
                'comment'        => $u['comment'] ?? null,
            ];

            if ($existing) {
                $this->db->update('pppoe_users', $userData, 'id = :id', ['id' => $existing['id']]);
            } else {
                $userData['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('pppoe_users', $userData);
            }
            $synced++;
        }

        $this->db->log('PPPoE', 'Sync Users', null, "Synced {$synced} PPPoE users to SQL DB", 'success', $routerId);
        return ['synced' => $synced, 'total_routeros' => count($users)];
    }
}
