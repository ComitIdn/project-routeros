<?php
/**
 * =========================================================================
 * Project RouterOS Framework - Universal JSON REST API Endpoint
 * =========================================================================
 *
 * Endpoint API RESTful universal untuk dihubungkan ke:
 * - Aplikasi Web Modern (React, Vue, Angular, Svelte, Next.js)
 * - Mobile App (Flutter, React Native, Android/iOS)
 * - Bot Telegram, WhatsApp, Discord
 * - Webhook & Integrasi Sistem Pembayaran (Payment Gateway)
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\Response;

// Load framework
$ros = RouterOS::withConfig(__DIR__ . '/config.php');

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Request parameters (support GET, POST form-urlencoded, and JSON body)
$inputJson = json_decode(file_get_contents('php://input'), true) ?: [];
$request = array_merge($_GET, $_POST, $inputJson);
$action = $request['action'] ?? ($request['endpoint'] ?? 'status');

try {
    switch ($action) {
        // --- STATUS & RESOURCES ---
        case 'status':
            $resources = $ros->system()->getResources();
            $identity = $ros->system()->getIdentity();
            $summary = $ros->hotspot()->getSummary();

            Response::json([
                'connected' => $ros->isConnected(),
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
        case 'generate_vouchers':
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && empty($inputJson)) {
                Response::json(null, 405, 'Method Not Allowed. Use POST.');
            }

            $count = max(1, min(100, (int)($request['count'] ?? 5)));
            $options = [
                'prefix'            => $request['prefix'] ?? 'VC-',
                'length'            => (int)($request['length'] ?? 6),
                'characters'        => $request['characters'] ?? 'alphanumeric',
                'user_mode'         => $request['user_mode'] ?? 'user_password',
                'profile'           => $request['profile'] ?? 'default',
                'limit-uptime'      => $request['limit_uptime'] ?? ($request['limit-uptime'] ?? ''),
                'limit-bytes-total' => $request['limit_bytes'] ?? ($request['limit-bytes-total'] ?? ''),
                'price'             => (float)($request['price'] ?? 0),
                'batch_tag'         => $request['batch_tag'] ?? ('api-' . date('Ymd-His')),
            ];

            $batch = $ros->voucher()->generateAndSave($count, $options);
            Response::json($batch, 201, "Successfully generated {$batch['generated']} vouchers");
            break;

        // --- KICK ACTIVE USER (POST) ---
        case 'kick':
            $target = $request['username'] ?? ($request['user'] ?? '');
            if (empty($target)) {
                Response::json(null, 400, 'Parameter "username" is required');
            }
            $res = $ros->hotspot()->disconnectUser($target);
            Response::json($res, 200, "User '{$target}' disconnected successfully");
            break;

        // --- BYPASS DEVICE MAC (POST) ---
        case 'bypass':
            $mac = $request['mac'] ?? ($request['mac_address'] ?? '');
            $comment = $request['comment'] ?? 'Bypassed via REST API';
            if (empty($mac)) {
                Response::json(null, 400, 'Parameter "mac" is required');
            }
            $res = $ros->hotspot()->bypassDevice($mac, null, $comment);
            Response::json($res, 200, "Device '{$mac}' bypassed successfully");
            break;

        // --- REALTIME TRAFFIC MONITOR ---
        case 'traffic':
            $iface = $request['interface'] ?? 'ether1';
            $traffic = $ros->interface()->monitorTraffic($iface, 1);
            Response::json($traffic, 200, "Realtime traffic for {$iface}");
            break;

        // --- NEW ACTION: FIND NAME TO ID ---
        case 'find_id':
        case 'findname_to_id':
            $name = $request['name'] ?? ($request['username'] ?? '');
            $path = $request['path'] ?? '/ip/hotspot/user';
            $id = $ros->find()->nameToId($name, $path);
            Response::json(['name' => $name, 'id' => $id, 'path' => $path], 200, "Resolved name to ID");
            break;

        // --- NEW ACTION: SET LIMIT ---
        case 'set_limit':
        case 'setlimit':
            $user = $request['username'] ?? ($request['name'] ?? '');
            $uptime = $request['limit_uptime'] ?? ($request['limit-uptime'] ?? null);
            $bytes = $request['limit_bytes'] ?? ($request['limit-bytes-total'] ?? null);
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->set()->limit($user, $uptime, $bytes, $path);
            Response::json($res, 200, "Limit updated for {$user}");
            break;

        // --- NEW ACTION: SET COMMENT ---
        case 'set_comment':
        case 'setcoment':
            $user = $request['username'] ?? ($request['name'] ?? '');
            $comment = $request['comment'] ?? '';
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->set()->comment($user, $comment, $path);
            Response::json($res, 200, "Comment updated for {$user}");
            break;

        // --- NEW ACTION: SET EMAIL ---
        case 'set_email':
        case 'setemail':
            $user = $request['username'] ?? ($request['name'] ?? '');
            $email = $request['email'] ?? '';
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->set()->email($user, $email, $path);
            Response::json($res, 200, "Email updated for {$user}");
            break;

        // --- NEW ACTION: SET NAME ---
        case 'set_name':
        case 'setname':
            $oldName = $request['old_name'] ?? ($request['name'] ?? ($request['id'] ?? ''));
            $newName = $request['new_name'] ?? '';
            $path = $request['path'] ?? '/ip/hotspot/user';
            $res = $ros->set()->name($oldName, $newName, $path);
            Response::json($res, 200, "Renamed '{$oldName}' to '{$newName}'");
            break;

        // --- NEW ACTION: SALES REPORT ---
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

        // --- GENERIC SUPERPOWER ACTIONS ---
        case 'generic_set':
            $path = $request['path'] ?? '';
            $idOrName = $request['id_or_name'] ?? ($request['name'] ?? '');
            $params = $request['params'] ?? [];
            $res = $ros->set()->set($path, $idOrName, $params);
            Response::json($res, 200, "Updated item on {$path}");
            break;

        case 'generic_get':
            $path = $request['path'] ?? '';
            $where = $request['where'] ?? [];
            $res = $ros->get()->get($path, $where);
            Response::json($res, 200, "Items retrieved from {$path}");
            break;

        case 'generic_remove':
            $path = $request['path'] ?? '';
            $idOrName = $request['id_or_name'] ?? ($request['name'] ?? '');
            $res = $ros->remove()->remove($path, $idOrName);
            Response::json($res, 200, "Item removed from {$path}");
            break;

        default:
            Response::json(null, 404, "Unknown action: '{$action}'. Refer to documentation for available actions.");
            break;
    }
} catch (\Throwable $e) {
    Response::json(['error' => $e->getMessage()], 500, 'Internal Server Error');
}
