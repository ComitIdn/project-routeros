<?php
/**
 * =========================================================================
 * Project RouterOS Framework - Universal JSON REST API Endpoint
 * =========================================================================
 *
 * Endpoint API RESTful universal untuk dihubungkan ke:
 * - Aplikasi Web Modern (React, Vue, Angular, Svelte, Next.js)
 * - Mobile App (Flutter, React Native, Android/iOS)
 * - Python / JS / Node.js Clients
 * - Bot Telegram, WhatsApp, Discord
 * - Webhook & Integrasi Sistem Pembayaran (Payment Gateway)
 * - PTMP Wireless Towers & Base Stations
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\Response;

$startTime = microtime(true);
$rawConfig = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
$apiConfig = $rawConfig['api'] ?? [];

// Enable Gzip compression if supported and configured (reduces JSON payload size by 85-90%)
if (!empty($apiConfig['gzip']) && extension_loaded('zlib') && !ob_get_level()) {
    ob_start('ob_gzhandler');
}

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Request parameters (support GET, POST form-urlencoded, and JSON body)
$inputJson = json_decode(file_get_contents('php://input'), true) ?: [];
$request = array_merge($_GET, $_POST, $inputJson);
$action = $request['action'] ?? ($request['endpoint'] ?? 'status');

// API Key / Bearer Token Authentication (Optional security protection)
$configuredApiKey = $apiConfig['key'] ?? '';
if (!empty($configuredApiKey)) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $bearerToken = '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $bearerToken = trim($matches[1]);
    }
    $passedKey = $_SERVER['HTTP_X_API_KEY'] ?? ($bearerToken ?: ($request['api_key'] ?? ''));

    if ($passedKey !== $configuredApiKey) {
        Response::json(['error' => 'Unauthorized: Invalid or missing API key'], 401, 'Unauthorized');
        exit;
    }
}

// Load framework
$ros = RouterOS::withConfig(__DIR__ . '/config.php');

// Optional runtime router ID switch for Multi-Router & Per-Router Session/Cache/Log isolation
if (!empty($request['router_id'])) {
    $ros->setRouterId((string)$request['router_id']);
}

// Optional runtime protocol switch: 'api', 'rest', 'ssh', 'telnet', 'auto'
if (!empty($request['protocol'])) {
    $ros->setProtocol($request['protocol'], !empty($request['port']) ? (int)$request['port'] : null);
}

try {
    switch ($action) {
        // --- STATUS & RESOURCES ---
        case 'status':
            $resources = $ros->system()->getResources();
            $identity = $ros->system()->getIdentity();
            $summary = $ros->hotspot()->getSummary();

            Response::json([
                'connected' => $ros->isConnected(),
                'protocol'  => $ros->getProtocol(),
                'identity'  => $identity[0]['name'] ?? 'MikroTik',
                'resources' => $resources[0] ?? [],
                'summary'   => $summary,
            ], 200, 'Router status retrieved');
            break;

        // --- HOTSPOT USERS ---
        case 'users':
            $profile = $request['profile'] ?? '';
            $users = $profile ? $ros->hotspot()->getUsersByProfile($profile) : $ros->hotspot()->getUsers();
            Response::json($users, 200, 'Hotspot users retrieved');
            break;

        // --- ACTIVE SESSIONS & LIVE TRAFFIC ---
        case 'active':
            $traffic = $ros->hotspot()->getActiveTraffic();
            Response::json($traffic, 200, 'Active hotspot traffic retrieved');
            break;

        // --- CONNECTED HOSTS ---
        case 'hosts':
            $hosts = $ros->hotspot()->getHosts();
            Response::json($hosts, 200, 'Hotspot hosts retrieved');
            break;

        // --- VOUCHERS (SQL & JSON DATABASE) ---
        case 'vouchers':
            $status = $request['status'] ?? 'active';
            $limit = (int)($request['limit'] ?? 50);
            $vouchers = $ros->voucher()->getFromDb(['status' => $status, 'limit' => $limit]);
            Response::json($vouchers, 200, 'Vouchers retrieved from database');
            break;

        // --- GENERATE VOUCHERS (POST) ---
        case 'generate_voucher':
            $count = (int)($request['count'] ?? 10);
            $profile = $request['profile'] ?? 'default';
            $timeLimit = $request['time_limit'] ?? ($request['limit_uptime'] ?? '1h');
            $price = (float)($request['price'] ?? 5000);
            $prefix = $request['prefix'] ?? 'VC';
            $comment = $request['comment'] ?? 'Voucher API';

            $result = $ros->voucher()->generate($count, [
                'profile'      => $profile,
                'limit_uptime' => $timeLimit,
                'price'        => $price,
                'prefix'       => $prefix,
                'comment'      => $comment,
            ]);

            Response::json($result, 201, 'Vouchers successfully generated');
            break;

        // --- HIGH-SPEED MASS BULK VOUCHERS (UP TO 10,000+) ---
        case 'generate_bulk_vouchers':
            $count = (int)($request['count'] ?? 100);
            $count = min(10000, max(1, $count));
            $opts = [
                'profile'           => $request['profile'] ?? 'default',
                'limit_uptime'      => $request['limit_uptime'] ?? ($request['time_limit'] ?? ''),
                'limit_bytes_total' => $request['limit_bytes'] ?? '',
                'price'             => (float)($request['price'] ?? 5000),
                'prefix'            => $request['prefix'] ?? 'VC',
                'length'            => (int)($request['length'] ?? 6),
                'characters'        => $request['characters'] ?? 'alphanumeric',
                'user_mode'         => $request['user_mode'] ?? 'user_password',
                'sync_mode'         => $request['sync_mode'] ?? 'auto',
                'batch_tag'         => $request['batch_tag'] ?? ($request['comment'] ?? 'batch-' . date('Ymd-His')),
                'save_db'           => true,
            ];
            $result = $ros->voucher()->generateFastBulk($count, $opts);
            Response::json($result, 201, "Generated {$result['generated']} bulk vouchers in {$result['duration_ms']}ms");
            break;

        // --- MULTI-OPERATION BATCH ENDPOINT ---
        case 'batch':
            $batchRequests = $request['requests'] ?? [];
            if (!is_array($batchRequests) || empty($batchRequests)) {
                Response::json(null, 400, 'Parameter requests must be an array of operation payloads');
            }
            $batchResults = [];
            foreach ($batchRequests as $idx => $req) {
                $subAction = $req['action'] ?? '';
                try {
                    $subRes = match($subAction) {
                        'status'     => ['connected' => $ros->isConnected(), 'resources' => $ros->system()->getResources()],
                        'users'      => $ros->hotspot()->getUsers(),
                        'active'     => $ros->hotspot()->getActiveTraffic(),
                        'hosts'      => $ros->hotspot()->getHosts(),
                        'vouchers'   => $ros->voucher()->getFromDb(['limit' => (int)($req['limit'] ?? 50)]),
                        'pppoe'      => $ros->pppoe()->getUsers(),
                        'interfaces' => $ros->interface()->getAll(),
                        default      => ['error' => "Unsupported batch action: {$subAction}"],
                    };
                    $batchResults[$idx] = ['action' => $subAction, 'status' => 'success', 'data' => $subRes];
                } catch (\Throwable $err) {
                    $batchResults[$idx] = ['action' => $subAction, 'status' => 'error', 'error' => $err->getMessage()];
                }
            }
            Response::json($batchResults, 200, 'Batch execution complete');
            break;

        // --- HOTSPOT PROFILE & ON-LOGIN SCRIPT ---
        case 'hotspot_create_profile':
            $name = $request['name'] ?? '';
            if (empty($name)) {
                Response::json(null, 400, 'Parameter name is required');
            }
            $rate = $request['rate_limit'] ?? '2M/5M';
            $validity = $request['validity'] ?? '1d';
            $profileOpts = [
                'shared_users'   => (string)($request['shared_users'] ?? '1'),
                'lock_mac'       => isset($request['lock_mac']) ? (bool)$request['lock_mac'] : true,
                'track_expiry'   => true,
                'telegram_token' => $request['telegram_token'] ?? '',
                'telegram_chat_id' => $request['telegram_chat_id'] ?? '',
            ];
            $res = $ros->hotspot()->createVoucherProfile($name, $rate, $validity, $profileOpts);
            Response::json($res, 201, "Hotspot profile {$name} created with automated on-login expiry");
            break;

        case 'hotspot_setup_mikhmon':
            $name = $request['name'] ?? '';
            if (empty($name)) {
                Response::json(null, 400, 'Parameter name is required');
            }
            $rate = $request['rate_limit'] ?? ($request['rate'] ?? '3M/2M');
            $validity = $request['validity'] ?? '24h';
            $price = (float)($request['price'] ?? 0);
            $sellingPrice = isset($request['selling_price']) ? (float)$request['selling_price'] : (isset($request['sprice']) ? (float)$request['sprice'] : $price);
            $expiryMode = $request['expiry_mode'] ?? ($request['mode'] ?? 'remc');
            $lockUser = $request['lock_user'] ?? ($request['lock'] ?? 'Disable');
            $parentQueue = $request['parent_queue'] ?? ($request['queue'] ?? 'none');
            $addressPool = $request['address_pool'] ?? ($request['pool'] ?? 'none');
            $sharedUsers = (string)($request['shared_users'] ?? '1');

            $res = $ros->hotspot()->setupMikhmonProfile(
                $name,
                $rate,
                $validity,
                $price,
                $sellingPrice,
                $expiryMode,
                $lockUser,
                $parentQueue,
                $addressPool,
                $sharedUsers,
                $request['options'] ?? []
            );
            Response::json($res, 201, "Mikhmon official Hotspot profile {$name} configured with parent-queue, address-pool and on-login script");
            break;

        case 'hotspot_generate_script':
            $name = $request['name'] ?? 'V24h';
            $rate = $request['rate_limit'] ?? ($request['rate'] ?? '3M/2M');
            $validity = $request['validity'] ?? '24h';
            $price = (float)($request['price'] ?? 8000);
            $sellingPrice = isset($request['selling_price']) ? (float)$request['selling_price'] : 10000;
            $expiryMode = $request['expiry_mode'] ?? 'remc';
            $lockUser = $request['lock_user'] ?? 'Disable';
            $parentQueue = $request['parent_queue'] ?? 'none';
            $addressPool = $request['address_pool'] ?? 'none';
            $sharedUsers = (string)($request['shared_users'] ?? '1');

            $script = $ros->hotspot()->generateMikhmonOfficialOnLoginScript(
                $name,
                $validity,
                $price,
                $sellingPrice,
                $expiryMode,
                $lockUser,
                $request['options'] ?? []
            );

            $cliCommand = $ros->hotspot()->generateMikhmonProfileCliCommand(
                $name,
                $rate,
                $validity,
                $price,
                $sellingPrice,
                $expiryMode,
                $lockUser,
                $parentQueue,
                $addressPool,
                $sharedUsers,
                $request['options'] ?? []
            );

            Response::json([
                'profile_name'   => $name,
                'rate_limit'     => $rate,
                'validity'       => $validity,
                'price'          => $price,
                'selling_price'  => $sellingPrice,
                'expiry_mode'    => $expiryMode,
                'lock_user'      => $lockUser,
                'parent_queue'   => $parentQueue,
                'address_pool'   => $addressPool,
                'shared_users'   => $sharedUsers,
                'on_login'       => $script,
                'cli_command'    => $cliCommand,
            ], 200, "Mikhmon Official Script & CLI Command generated");
            break;

        case 'ip_pools':
            $poolList = $ros->hotspot()->getPoolList();
            $rawPools = $ros->ip()->getPools();
            Response::json([
                'dropdown_options' => $poolList,
                'pools'            => $rawPools,
            ], 200, "IP Pools list for dropdown");
            break;

        case 'parent_queues':
            $queueList = $ros->hotspot()->getParentQueueList();
            Response::json([
                'dropdown_options' => $queueList,
            ], 200, "Parent simple queues list for dropdown");
            break;

        // --- FIREWALL SECURITY HARDENING ---
        case 'firewall_harden':
            $lan = $request['lan_interface'] ?? 'bridge';
            $wan = $request['wan_interface'] ?? null;
            $res = $ros->firewall()->hardenServerBaseline($lan, $wan);
            Response::json($res, 200, "Enterprise server firewall hardened");
            break;

        case 'firewall_bruteforce':
            $ports = !empty($request['ports']) ? (is_array($request['ports']) ? $request['ports'] : explode(',', $request['ports'])) : [22, 8291, 8728, 8729];
            $res = $ros->firewall()->protectBruteForce($ports);
            Response::json($res, 200, "Brute-force protection rules active");
            break;

        case 'firewall_fasttrack':
            $res = $ros->firewall()->enableFastTrack();
            Response::json($res, 200, "FastTrack connection acceleration enabled");
            break;

        // --- SERVER INTERFACES ---
        case 'interfaces':
            $res = $ros->interface()->getAll();
            Response::json($res, 200, "All router interfaces");
            break;

        case 'sfp':
            $res = $ros->interface()->getSfp();
            Response::json($res, 200, "SFP fiber interfaces");
            break;

        case 'bonding':
            $res = $ros->interface()->getBonding();
            Response::json($res, 200, "LACP Bonding interfaces");
            break;

        case 'vrrp':
            $res = $ros->interface()->getVrrp();
            Response::json($res, 200, "VRRP HA interfaces");
            break;

        case 'vlans':
            $res = $ros->interface()->getVlans();
            Response::json($res, 200, "VLAN 802.1Q interfaces");
            break;

        case 'interface_traffic':
            $iface = $request['interface'] ?? 'ether1';
            $res = $ros->interface()->monitorTraffic($iface);
            Response::json($res, 200, "Realtime traffic on {$iface}");
            break;

        // --- DATABASE OPERATIONAL STATS ---
        case 'database_stats':
            $stats = $ros->db() ? $ros->db()->stats() : ['driver' => 'none', 'message' => 'Database not enabled'];
            Response::json($stats, 200, "Database operational metrics");
            break;

        // --- KICK USER ---
        case 'kick':
            $username = $request['username'] ?? '';
            if (empty($username)) {
                Response::json(null, 400, 'Parameter username is required');
            }
            $result = $ros->hotspot()->kickUser($username);
            Response::json($result, 200, "User {$username} disconnected");
            break;

        // --- FIND NAME TO ID ---
        case 'find_id':
            $name = $request['name'] ?? ($request['username'] ?? '');
            $path = $request['path'] ?? '/ip/hotspot/user';
            $id = $ros->findNameToId($name, $path);
            Response::json(['name' => $name, 'id' => $id, 'path' => $path], 200, "ID lookup complete");
            break;

        // --- GRANULAR SETTERS ---
        case 'set_limit':
            $name = $request['name'] ?? ($request['username'] ?? '');
            $uptime = $request['limit_uptime'] ?? ($request['uptime'] ?? null);
            $bytes = $request['limit_bytes'] ?? ($request['bytes'] ?? null);
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->setLimit($name, $uptime, $bytes, $path);
            Response::json($res, 200, "Limit updated for {$name}");
            break;

        case 'set_comment':
            $name = $request['name'] ?? ($request['username'] ?? '');
            $comment = $request['comment'] ?? '';
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->setComment($name, $comment, $path);
            Response::json($res, 200, "Comment updated for {$name}");
            break;

        case 'set_email':
            $name = $request['name'] ?? ($request['username'] ?? '');
            $email = $request['email'] ?? '';
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->setEmail($name, $email, $path);
            Response::json($res, 200, "Email updated for {$name}");
            break;

        case 'set_name':
            $idOrOld = $request['id'] ?? ($request['old_name'] ?? '');
            $newName = $request['new_name'] ?? ($request['name'] ?? '');
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->setName($idOrOld, $newName, $path);
            Response::json($res, 200, "Name updated to {$newName}");
            break;

        // --- SALES REPORT ---
        case 'sales_report':
            $res = $ros->voucher()->getSalesReport();
            Response::json($res, 200, "Voucher sales report");
            break;

        // --- PPPOE ENDPOINTS ---
        case 'pppoe_users':
            $users = $ros->pppoe()->getUsers();
            Response::json($users, 200, "PPPoE users list");
            break;

        case 'pppoe_active':
            $active = $ros->pppoe()->getActive();
            Response::json($active, 200, "Active PPPoE sessions");
            break;

        case 'pppoe_suspend':
            $user = $request['username'] ?? '';
            $profile = $request['profile'] ?? 'ISOLIR';
            $res = $ros->pppoe()->suspendCustomer($user, $profile);
            Response::json($res, 200, "Customer {$user} suspended");
            break;

        case 'pppoe_unsuspend':
            $user = $request['username'] ?? '';
            $profile = $request['profile'] ?? 'default';
            $res = $ros->pppoe()->unsuspendCustomer($user, $profile);
            Response::json($res, 200, "Customer {$user} unsuspended");
            break;

        case 'pppoe_bind_caller':
            $user = $request['username'] ?? '';
            $mac = $request['mac'] ?? '';
            $res = $ros->pppoe()->bindCallerId($user, $mac);
            Response::json($res, 200, "Bound {$user} to MAC {$mac}");
            break;

        case 'pppoe_setup_mikhmon':
            $profileName = $request['profile'] ?? 'ISOLIR';
            $options = $request['options'] ?? [
                'local_address'      => $request['local_address'] ?? '10.0.99.1',
                'remote_address'     => $request['remote_address'] ?? '10.0.99.2-10.0.99.254',
                'rate_limit'         => $request['rate_limit'] ?? '128k/128k',
                'address_list'       => $request['address_list'] ?? 'ISOLIR_LIST',
                'nat_redirect'       => isset($request['nat_redirect']) ? (bool)$request['nat_redirect'] : true,
                'install_scheduler'  => isset($request['install_scheduler']) ? (bool)$request['install_scheduler'] : true,
                'scheduler_interval' => $request['scheduler_interval'] ?? '1h',
            ];
            $res = $ros->pppoe()->setupMikhmonIsolirProfile($profileName, $options);
            Response::json($res, 201, "Mikhmon PPPoE Isolir profile and scheduler configured");
            break;

        case 'pppoe_customer_profile':
            $name = $request['name'] ?? 'P3';
            $rate = $request['rate_limit'] ?? '3M/3M';
            $local = $request['local_address'] ?? '192.168.100.1';
            $remote = $request['remote_address'] ?? 'isolir';
            $validity = $request['validity'] ?? '30d';
            $options = $request['options'] ?? [];
            if (isset($request['parent_queue'])) $options['parent_queue'] = $request['parent_queue'];
            if (isset($request['dns_server'])) $options['dns_server'] = $request['dns_server'];
            if (isset($request['auto_isolir'])) $options['auto_isolir'] = (bool)$request['auto_isolir'];
            if (isset($request['pool_ranges'])) $options['pool_ranges'] = $request['pool_ranges'];

            $res = $ros->pppoe()->setupMikhmonCustomerProfile($name, $rate, $local, $remote, $validity, $options);
            Response::json($res, 201, "Mikhmon PPPoE customer profile {$name} configured with auto-isolir");
            break;

        case 'mikhmon_schedulers':
            $type = $request['type'] ?? 'all';
            $sub = $request['sub_action'] ?? 'install';
            if ($sub === 'get') {
                $scheds = \RouterOSFramework\Core\Mikhmon::getAllSchedulers();
                Response::json($scheds, 200, "Mikhmon schedulers definitions retrieved");
            } else {
                $results = [];
                if ($type === 'all' || $type === 'hotspot') {
                    $results['hotspot'] = $ros->hotspot()->installMikhmonCoreSchedulers();
                }
                if ($type === 'all' || $type === 'pppoe') {
                    $results['pppoe'] = $ros->pppoe()->installMikhmonPppoeIsolirScheduler();
                }
                Response::json($results, 201, "Mikhmon schedulers installed/updated on RouterOS");
            }
            break;

        case 'ip_ensure_pool':
            $poolName = $request['name'] ?? '';
            $ranges = $request['ranges'] ?? '';
            $nextPool = $request['next_pool'] ?? '';
            if (empty($poolName) || empty($ranges)) {
                Response::json(['error' => 'Pool name and ranges are required'], 400, "Validation failed");
            }
            $res = $ros->ip()->ensurePool($poolName, $ranges, $nextPool);
            Response::json($res, 201, "IP pool {$poolName} ensured on RouterOS");
            break;

        case 'mikhmon_setup_all':
            $config = $request['config'] ?? $request;
            $res = $ros->setupMikhmonAll($config);
            Response::json($res, 201, "Mikhmon official infrastructure bootstrapped successfully");
            break;

        case 'pppoe_reconnect':
            $user = $request['username'] ?? '';
            $profile = $request['profile'] ?? 'default';
            $dueDate = $request['due_date'] ?? null;
            $comment = $request['comment'] ?? null;
            $res = $ros->pppoe()->reconnectCustomer($user, $profile, $dueDate, $comment);
            Response::json($res, 200, "Customer {$user} reconnected successfully");
            break;

        case 'pppoe_isolate':
            $user = $request['username'] ?? '';
            $profile = $request['profile'] ?? 'ISOLIR';
            $reason = $request['reason'] ?? 'Jatuh Tempo';
            $res = $ros->pppoe()->isolateCustomer($user, $profile, $reason);
            Response::json($res, 200, "Customer {$user} isolated");
            break;

        case 'pppoe_auto_isolate':
            $profile = $request['profile'] ?? 'ISOLIR';
            $currentDate = $request['date'] ?? '';
            $res = $ros->pppoe()->autoIsolateOverdue($profile, $currentDate);
            Response::json($res, 200, "Auto-isolate sweep executed");
            break;

        // --- PER-ROUTER SESSION & MULTI-LOGGING ENDPOINTS ---
        case 'router_session':
            $sub = $request['sub_action'] ?? 'get';
            $session = $ros->session();
            if ($sub === 'set') {
                $key = $request['key'] ?? '';
                $val = $request['val'] ?? null;
                if ($key) $session->set($key, $val);
                Response::json(['status' => 'set', 'key' => $key, 'session' => $session->all()], 200, "Session key updated");
            } elseif ($sub === 'clear' || $sub === 'destroy') {
                $session->destroy();
                Response::json(['status' => 'destroyed'], 200, "Router session cleared");
            } else {
                Response::json([
                    'router_id'  => $ros->getRouterId(),
                    'session_id' => $session->getSessionId(),
                    'token'      => $session->getToken(),
                    'is_valid'   => $session->isValid(),
                    'data'       => $session->all(),
                ], 200, "Router isolated session");
            }
            break;

        case 'router_logs':
            $limit = (int)($request['limit'] ?? 50);
            $logs = $ros->getLogs($limit);
            Response::json([
                'router_id' => $ros->getRouterId(),
                'total'     => count($logs),
                'logs'      => $logs,
            ], 200, "Multi-logging history for Router ID {$ros->getRouterId()}");
            break;

        // --- MULTI-CHAT BOT BROADCAST (TELEGRAM & WHATSAPP) ---
        case 'bot_broadcast':
            $msg = $request['message'] ?? '';
            if (empty($msg)) {
                Response::json(null, 400, 'Parameter message is required');
            }
            $target = $request['channel'] ?? 'all'; // 'telegram', 'whatsapp', or 'all'
            $bot = $ros->bot();
            if ($target === 'telegram') {
                $res = $bot->sendTelegram($msg);
            } elseif ($target === 'whatsapp') {
                $res = $bot->sendWhatsApp($msg);
            } else {
                $res = $bot->broadcast($msg);
            }
            Response::json($res, 200, "Broadcast dispatched to notification channels");
            break;

        // --- MULTI-PTMP ROUTEROS CONNECTION ENDPOINTS ---
        case 'ptmp_detect':
            $res = $ros->ptmp()->detectHardware();
            Response::json($res, 200, "PTMP Hardware detection");
            break;

        case 'ptmp_stations':
            $iface = $request['interface'] ?? null;
            $res = $ros->ptmp()->getStations($iface);
            Response::json($res, 200, "Connected PTMP CPE stations");
            break;

        case 'ptmp_metrics':
            $iface = $request['interface'] ?? null;
            $res = $ros->ptmp()->getStationQualityMetrics($iface);
            Response::json($res, 200, "PTMP link quality metrics");
            break;

        case 'ptmp_access_list':
            $res = $ros->ptmp()->getAccessList();
            Response::json($res, 200, "PTMP Access list rules");
            break;

        case 'ptmp_add_access':
            $mac = $request['mac'] ?? '';
            $iface = $request['interface'] ?? null;
            $signal = $request['signal_range'] ?? null;
            $comment = $request['comment'] ?? null;
            $allow = isset($request['allow']) ? (bool)$request['allow'] : true;
            $res = $ros->ptmp()->addAccessList($mac, $iface, $signal, $comment, $allow);
            Response::json($res, 201, "Added MAC {$mac} to PTMP Access List");
            break;

        case 'ptmp_remove_access':
            $macOrId = $request['id'] ?? ($request['mac'] ?? '');
            $res = $ros->ptmp()->removeAccessList($macOrId);
            Response::json($res, 200, "Removed {$macOrId} from PTMP Access List");
            break;

        case 'ptmp_kick':
            $mac = $request['mac'] ?? '';
            $res = $ros->ptmp()->kickStation($mac);
            Response::json($res, 200, "Kicked PTMP station {$mac}");
            break;

        case 'ptmp_set_channel':
            $iface = $request['interface'] ?? '';
            if (empty($iface)) {
                Response::json(null, 400, 'Parameter interface is required (server routers do not have default wlan interfaces)');
            }
            $freq = $request['frequency'] ?? 'auto';
            $band = $request['band'] ?? '5ghz-only-n';
            $width = $request['width'] ?? '20/40mhz-XX';
            $res = $ros->ptmp()->setChannel($iface, $freq, $band, $width);
            Response::json($res, 200, "Channel updated on {$iface}");
            break;

        case 'ptmp_set_protocol':
            $iface = $request['interface'] ?? '';
            if (empty($iface)) {
                Response::json(null, 400, 'Parameter interface is required');
            }
            $proto = $request['wireless_protocol'] ?? ($request['protocol'] ?? 'nv2');
            $res = $ros->ptmp()->setProtocol($iface, $proto);
            Response::json($res, 200, "Wireless protocol on {$iface} set to {$proto}");
            break;

        case 'ptmp_set_nv2':
            $iface = $request['interface'] ?? '';
            if (empty($iface)) {
                Response::json(null, 400, 'Parameter interface is required');
            }
            $opts = $request['options'] ?? [];
            $res = $ros->ptmp()->setNv2Config($iface, $opts);
            Response::json($res, 200, "NV2 config updated on {$iface}");
            break;

        // --- UNIVERSAL FULL CRUD ACTIONS ---
        case 'create':
        case 'generic_create':
            $path = $request['path'] ?? '';
            $data = $request['data'] ?? ($request['params'] ?? []);
            $res = $ros->create()->create($path, $data);
            Response::json($res, 201, "Created item on {$path}");
            break;

        case 'write':
        case 'generic_write':
            $path = $request['path'] ?? '';
            $params = $request['params'] ?? [];
            $res = $ros->write()->write($path, $params);
            Response::json($res, 200, "Executed write on {$path}");
            break;

        case 'read':
        case 'generic_read':
        case 'generic_get':
            $path = $request['path'] ?? '';
            $where = $request['where'] ?? [];
            $res = $ros->read()->read($path, $where);
            Response::json($res, 200, "Items retrieved from {$path}");
            break;

        case 'generic_set':
            $path = $request['path'] ?? '';
            $idOrName = $request['id_or_name'] ?? ($request['name'] ?? '');
            $params = $request['params'] ?? [];
            $res = $ros->set()->set($path, $idOrName, $params);
            Response::json($res, 200, "Updated item on {$path}");
            break;

        case 'generic_remove':
            $path = $request['path'] ?? '';
            $idOrName = $request['id_or_name'] ?? ($request['name'] ?? '');
            $res = $ros->remove()->remove($path, $idOrName);
            Response::json($res, 200, "Item removed from {$path}");
            break;

        case 'generic_toggle':
            $path = $request['path'] ?? '';
            $idOrName = $request['id_or_name'] ?? ($request['name'] ?? '');
            $res = $ros->toggle()->toggle($path, $idOrName);
            Response::json($res, 200, "Toggled item status on {$path}");
            break;

        case 'terminal':
            $cmd = $request['cmd'] ?? ($request['command'] ?? '');
            $res = $ros->write()->terminal($cmd);
            Response::json(['command' => $cmd, 'output' => $res], 200, "Command executed");
            break;

        default:
            Response::json(null, 404, "Unknown action: '{$action}'. Refer to documentation for available actions.");
            break;
    }
} catch (\Throwable $e) {
    Response::json(['error' => $e->getMessage()], 500, 'Internal Server Error');
}
