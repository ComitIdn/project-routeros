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
