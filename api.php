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

// Load framework
$ros = RouterOS::withConfig(__DIR__ . '/config.php');

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Request parameters (support GET, POST form-urlencoded, and JSON body)
$inputJson = json_decode(file_get_contents('php://input'), true) ?: [];
$request = array_merge($_GET, $_POST, $inputJson);
$action = $request['action'] ?? ($request['endpoint'] ?? 'status');

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
            $timeLimit = $request['time_limit'] ?? '1h';
            $price = (float)($request['price'] ?? 5000);
            $prefix = $request['prefix'] ?? 'VC';
            $comment = $request['comment'] ?? 'Voucher API';

            $result = $ros->voucher()->generate([
                'count'      => $count,
                'profile'    => $profile,
                'time_limit' => $timeLimit,
                'price'      => $price,
                'prefix'     => $prefix,
                'comment'    => $comment,
            ]);

            Response::json($result, 201, 'Vouchers successfully generated');
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

        // --- MULTI-PTMP WIRELESS ENDPOINTS ---
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
            $iface = $request['interface'] ?? 'wlan1';
            $freq = $request['frequency'] ?? 'auto';
            $band = $request['band'] ?? '5ghz-only-n';
            $width = $request['width'] ?? '20/40mhz-XX';
            $res = $ros->ptmp()->setChannel($iface, $freq, $band, $width);
            Response::json($res, 200, "Channel updated on {$iface}");
            break;

        case 'ptmp_set_protocol':
            $iface = $request['interface'] ?? 'wlan1';
            $proto = $request['wireless_protocol'] ?? ($request['protocol'] ?? 'nv2');
            $res = $ros->ptmp()->setProtocol($iface, $proto);
            Response::json($res, 200, "Wireless protocol on {$iface} set to {$proto}");
            break;

        case 'ptmp_set_nv2':
            $iface = $request['interface'] ?? 'wlan1';
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
