<?php
/**
 * =========================================================================
 * Project RouterOS Framework - Interactive CLI Console Tool
 * =========================================================================
 *
 * Jalankan langsung dari Terminal / Termux:
 * php console.php status
 * php console.php protocol rest
 * php console.php ptmp stations
 * php console.php ptmp access
 * php console.php ptmp kick 00:11:22:33:44:55
 * php console.php ptmp channel wlan1 5180
 * php console.php create /ip/address address=10.10.10.1/24 interface=ether2
 * php console.php read /ip/address
 * php console.php write /system/reboot
 * php console.php terminal "/interface wireless print"
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\Helper;

$ros = RouterOS::withConfig(__DIR__ . '/config.php');

$args = array_slice($argv, 1);
$command = strtolower($args[0] ?? 'help');

echo "\n⚡ MIKROTIK ROUTEROS FRAMEWORK CLI\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

switch ($command) {
    case 'status':
        $res = $ros->system()->getResources();
        $identity = $ros->system()->getIdentity();
        $summary = $ros->hotspot()->getSummary();

        echo "📡 Router Identity : " . ($identity[0]['name'] ?? 'MikroTik') . "\n";
        echo "🌐 Protocol        : " . strtoupper($ros->getProtocol()) . "\n";
        echo "🖥️  Board Name      : " . ($res[0]['board-name'] ?? '-') . "\n";
        echo "⚙️  CPU Load        : " . ($res[0]['cpu-load'] ?? '0') . "%\n";
        echo "💾 Free Memory     : " . (isset($res[0]['free-memory']) ? Helper::formatBytes((float)$res[0]['free-memory']) : '-') . "\n";
        echo "⏱️  Uptime          : " . ($res[0]['uptime'] ?? '-') . "\n";
        echo "👥 Hotspot Users   : " . ($summary['total_users'] ?? 0) . "\n";
        echo "🟢 Active Sessions : " . ($summary['active_users'] ?? 0) . "\n";
        echo "📱 Detected Hosts  : " . ($summary['total_hosts'] ?? 0) . "\n";
        break;

    case 'protocol':
    case 'proto':
        $proto = $args[1] ?? '';
        if (!$proto) {
            echo "🌐 Protokol aktif saat ini: " . strtoupper($ros->getProtocol()) . "\n";
            echo "   Pilihan protokol: api, rest, ssh, telnet, auto\n";
            break;
        }
        $port = isset($args[2]) ? (int)$args[2] : null;
        $ok = $ros->setProtocol($proto, $port);
        echo $ok ? "✅ Berhasil berganti ke protokol: " . strtoupper($proto) . "\n" : "❌ Gagal koneksi via " . strtoupper($proto) . "\n";
        break;

    case 'voucher':
    case 'vc':
        $count = isset($args[1]) ? (int)$args[1] : 5;
        $profile = $args[2] ?? 'default';
        $uptime = $args[3] ?? '2h';
        $bytes = $args[4] ?? '';

        echo "⏳ Membuat {$count} voucher paket '{$profile}' (Uptime: {$uptime})...\n";
        $batch = $ros->voucher()->generateAndSave($count, [
            'prefix'            => 'VC-',
            'profile'           => $profile,
            'limit-uptime'      => $uptime,
            'limit-bytes-total' => $bytes,
            'batch_tag'         => 'cli-' . date('Ymd-His'),
        ]);

        if (isset($batch['error'])) {
            echo "❌ Gagal: {$batch['error']}\n";
        } else {
            echo "✅ {$batch['count']} voucher berhasil dibuat dan disimpan!\n";
            echo "ID Batch: {$batch['batch_id']}\n\n";
            echo sprintf("%-15s | %-15s | %-10s\n", "Username", "Password", "Profile");
            echo "------------------------------------------------\n";
            foreach ($batch['vouchers'] as $v) {
                echo sprintf("%-15s | %-15s | %-10s\n", $v['username'], $v['password'], $v['profile']);
            }
        }
        break;

    case 'kick':
        $user = $args[1] ?? '';
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php kick [username]\n";
            exit(1);
        }
        $res = $ros->hotspot()->kickUser($user);
        echo !isset($res['error']) ? "✅ User '{$user}' berhasil diputuskan dari hotspot.\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'bypass':
        $mac = $args[1] ?? '';
        $comment = $args[2] ?? 'CLI-Bypass';
        if (!$mac) {
            echo "⚠️  Penggunaan: php console.php bypass [mac_address] [comment]\n";
            exit(1);
        }
        $res = $ros->hotspot()->bypassDevice($mac, $comment);
        echo !isset($res['error']) ? "✅ Perangkat MAC '{$mac}' berhasil di-bypass.\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'backup':
        $name = $args[1] ?? ('backup-' . date('Ymd-His'));
        $res = $ros->backup()->createBinaryBackup($name);
        echo !isset($res['error']) ? "✅ Backup berhasil dibuat dengan nama: {$name}\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'wol':
        $mac = $args[1] ?? '';
        $interface = $args[2] ?? 'ether1';
        if (!$mac) {
            echo "⚠️  Penggunaan: php console.php wol [mac_address] [interface]\n";
            exit(1);
        }
        $res = $ros->tool()->wakeOnLan($mac, $interface);
        echo !isset($res['error']) ? "✅ Paket Wake-on-LAN berhasil dikirim ke {$mac}\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'setlimit':
        $user = $args[1] ?? '';
        $uptime = $args[2] ?? null;
        $bytes = $args[3] ?? null;
        if (!$user || (!$uptime && !$bytes)) {
            echo "⚠️  Penggunaan: php console.php setlimit [username] [limit-uptime] [optional:limit-bytes]\n";
            exit(1);
        }
        $res = $ros->setLimit($user, $uptime, $bytes);
        echo !isset($res['error']) ? "✅ Limit user '{$user}' berhasil diupdate.\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'setcomment':
        $user = $args[1] ?? '';
        $comment = $args[2] ?? '';
        if (!$user || !$comment) {
            echo "⚠️  Penggunaan: php console.php setcomment [username] [comment]\n";
            exit(1);
        }
        $res = $ros->setComment($user, $comment);
        echo !isset($res['error']) ? "✅ Komentar user '{$user}' berhasil diubah.\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'setemail':
        $user = $args[1] ?? '';
        $email = $args[2] ?? '';
        if (!$user || !$email) {
            echo "⚠️  Penggunaan: php console.php setemail [username] [email]\n";
            exit(1);
        }
        $res = $ros->setEmail($user, $email);
        echo !isset($res['error']) ? "✅ Email user '{$user}' berhasil diubah.\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'setname':
        $oldName = $args[1] ?? '';
        $newName = $args[2] ?? '';
        if (!$oldName || !$newName) {
            echo "⚠️  Penggunaan: php console.php setname [oldName/id] [newName]\n";
            exit(1);
        }
        $res = $ros->setName($oldName, $newName);
        echo !isset($res['error']) ? "✅ Nama berhasil diubah dari '{$oldName}' menjadi '{$newName}'\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'findid':
        $name = $args[1] ?? '';
        $path = $args[2] ?? '/ip/hotspot/user';
        if (!$name) {
            echo "⚠️  Penggunaan: php console.php findid [name] [optional:path]\n";
            exit(1);
        }
        $id = $ros->findNameToId($name, $path);
        echo $id ? "🔍 ID untuk '{$name}' pada {$path} adalah: {$id}\n" : "❌ Item '{$name}' tidak ditemukan pada {$path}\n";
        break;

    case 'suspend':
        $user = $args[1] ?? '';
        $profile = $args[2] ?? 'ISOLIR';
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php suspend [pppoe_username] [optional:profile]\n";
            exit(1);
        }
        $res = $ros->pppoe()->suspendCustomer($user, $profile);
        echo !isset($res['error']) ? "⛔ Pelanggan '{$user}' berhasil diisolir (Profile: {$profile})\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'unsuspend':
        $user = $args[1] ?? '';
        $profile = $args[2] ?? 'default';
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php unsuspend [pppoe_username] [optional:profile]\n";
            exit(1);
        }
        $res = $ros->pppoe()->unsuspendCustomer($user, $profile);
        echo !isset($res['error']) ? "✅ Isolir pelanggan '{$user}' dibuka (Profile: {$profile})\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'reconnect':
        $user = $args[1] ?? '';
        $profile = $args[2] ?? 'default';
        $dueDate = $args[3] ?? null;
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php reconnect [pppoe_username] [profile] [optional:due_date]\n";
            exit(1);
        }
        $res = $ros->pppoe()->reconnectCustomer($user, $profile, $dueDate);
        echo !isset($res['error']) ? "🔄 Pelanggan '{$user}' berhasil diaktifkan kembali (Profile: {$profile}" . ($dueDate ? ", Jatuh Tempo: {$dueDate}" : "") . ")\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'isolate':
        $user = $args[1] ?? '';
        $reason = $args[2] ?? 'Jatuh Tempo';
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php isolate [pppoe_username] [optional:alasan]\n";
            exit(1);
        }
        $res = $ros->pppoe()->isolateCustomer($user, 'ISOLIR', $reason);
        echo !isset($res['error']) ? "⛔ Pelanggan '{$user}' diisolir ({$reason})\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'hotspot:profile':
    case 'profile:hotspot':
    case 'mikhmon:hotspot':
        $profile = $args[1] ?? 'V24h';
        $rate = $args[2] ?? '3M/2M';
        $validity = $args[3] ?? '24h';
        $price = (float)($args[4] ?? 8000);
        $sPrice = isset($args[5]) ? (float)$args[5] : 10000;
        $mode = $args[6] ?? 'remc';
        $lock = $args[7] ?? 'Disable';
        $pool = $args[8] ?? 'none';
        $queue = $args[9] ?? 'none';
        $shared = $args[10] ?? '1';

        $res = $ros->hotspot()->setupProfile(
            $profile,
            $rate,
            $validity,
            $price,
            $sPrice,
            $mode,
            $lock,
            $queue,
            $pool,
            $shared
        );

        echo "✨ Hotspot Profile Configured:\n";
        echo "   Name         : {$profile}\n";
        echo "   Rate Limit   : {$rate}\n";
        echo "   Validity     : {$validity}\n";
        echo "   Price        : Rp " . number_format($price, 0, ',', '.') . "\n";
        echo "   Selling Price: Rp " . number_format($sPrice, 0, ',', '.') . "\n";
        echo "   Mode         : {$mode}\n";
        echo "   Lock User    : {$lock}\n";
        echo "   Parent Queue : {$queue}\n";
        echo "   Address Pool : {$pool}\n";
        echo "   Shared Users : {$shared}\n";
        echo "   RouterOS Res : " . json_encode($res['result'] ?? $res) . "\n";
        break;

    case 'pools':
    case 'mikhmon:pools':
        $pools = $ros->hotspot()->getPoolList();
        echo "📋 Available IP Pools on RouterOS:\n";
        foreach ($pools as $idx => $p) {
            echo "   [{$idx}] {$p}\n";
        }
        break;

    case 'queues':
    case 'mikhmon:queues':
        $queues = $ros->hotspot()->getParentQueueList();
        echo "📋 Available Parent Simple Queues on RouterOS:\n";
        foreach ($queues as $idx => $q) {
            echo "   [{$idx}] {$q}\n";
        }
        break;

    case 'pppoe:profile':
    case 'profile:pppoe':
    case 'mikhmon:pppoe':
        $profile = $args[1] ?? 'P3';
        $rate = $args[2] ?? '3M/3M';
        $local = $args[3] ?? '192.168.100.1';
        $remote = $args[4] ?? 'isolir';
        $validity = $args[5] ?? '30d';

        $res = $ros->pppoe()->setupCustomerProfile($profile, $rate, $local, $remote, $validity);
        echo "✨ PPPoE Customer Profile Configured:\n";
        echo "   Name         : {$profile}\n";
        echo "   Rate Limit   : {$rate}\n";
        echo "   Local Address: {$local}\n";
        echo "   Remote Pool  : {$remote}\n";
        echo "   Validity     : {$validity}\n";
        echo "   Auto-Isolir  : Pool, Profile & Hourly Scheduler ensured\n";
        echo "   RouterOS Res : " . json_encode($res['result'] ?? $res) . "\n";
        break;

    case 'isolir':
    case 'pppoe:isolir':
    case 'mikhmon:isolir':
        $poolRange = $args[1] ?? '10.10.10.2-10.10.10.254';
        $local = $args[2] ?? '10.10.10.255';
        $rate = $args[3] ?? '512k/512k';
        $res = $ros->pppoe()->installIsolirInfrastructure($poolRange, $local, $rate);
        echo "✨ PPPoE Isolir Infrastructure Configured:\n";
        echo "   Pool isolir   : {$poolRange}\n";
        echo "   Profile isolir: {$rate} (GW: {$local})\n";
        echo "   Scheduler     : hourly at startup (drops active when overdue)\n";
        echo "   Result        : " . json_encode($res) . "\n";
        break;

    case 'schedulers':
    case 'schedulers:install':
    case 'schedulers:list':
    case 'mikhmon:schedulers':
        $action = $args[1] ?? ($command === 'schedulers:list' ? 'list' : 'install');
        if ($action === 'list') {
            $scheds = \RouterOSFramework\Core\AutomationEngine::getAllSchedulers();
            echo "📋 Core Schedulers Registry (Total: " . count($scheds) . "):\n";
            foreach ($scheds as $k => $s) {
                echo "   - [{$k}] Interval: {$s['interval']} | Start: {$s['start-time']} | {$s['comment']}\n";
            }
        } else {
            $hRes = $ros->hotspot()->installCoreSchedulers();
            $pRes = $ros->pppoe()->installIsolirScheduler();
            echo "✨ Core Schedulers Installed on RouterOS:\n";
            echo "   [1] autoremove-report      : " . json_encode($hRes['autoremove-report'] ?? 'ok') . "\n";
            echo "   [2] Expire-Monitor         : " . json_encode($hRes['Expire-Monitor'] ?? 'ok') . "\n";
            echo "   [3] Live-Report-Monitor    : " . json_encode($hRes['Live-Report-Monitor'] ?? 'ok') . "\n";
            echo "   [4] Reset-Expire-Monitor   : " . json_encode($hRes['Reset-Expire-Monitor'] ?? 'ok') . "\n";
            echo "   [5] Reset-Expire-Monitor-D : " . json_encode($hRes['Reset-Expire-Monitor-Daily'] ?? 'ok') . "\n";
            echo "   [6] isolir (PPPoE Monitor) : " . json_encode($pRes) . "\n";
        }
        break;

    case 'setup-all':
    case 'bootstrap':
    case 'mikhmon:setup-all':
        echo "🚀 Bootstrapping complete automation ecosystem on RouterOS...\n";
        $res = $ros->setupAll([
            'hotspot_profile' => [
                'name'         => $args[1] ?? 'V15D',
                'rate_limit'   => '3M/2M',
                'validity'     => '15d',
                'price'        => 0,
                'selling_price'=> 10000,
                'address_pool' => 'hs-pool-22',
                'options'      => ['pool_ranges' => '175.12.0.1-175.12.10.0, 175.12.10.2-175.12.255.254']
            ],
            'pppoe_profile'   => [
                'name'          => 'P3',
                'rate_limit'    => '3M/3M',
                'local_address' => '192.168.100.1',
                'remote_address'=> 'isolir',
                'validity'      => '30d'
            ]
        ]);
        echo "✅ Bootstrap complete!\n";
        echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        break;

    case 'broadcast':
        $msg = $args[1] ?? '';
        if (!$msg) {
            echo "⚠️  Penggunaan: php console.php broadcast \"Pesan Pengumuman\"\n";
            exit(1);
        }
        $res = $ros->bot()->broadcast($msg);
        echo "📢 Hasil Broadcast:\n";
        echo "   Telegram : " . ($res['telegram']['sent'] ?? 0) . "/" . ($res['telegram']['total'] ?? 0) . " terkirim\n";
        echo "   WhatsApp : " . ($res['whatsapp']['sent'] ?? 0) . "/" . ($res['whatsapp']['total'] ?? 0) . " terkirim\n";
        break;

    case 'session':
        $sess = $ros->session();
        echo "🔐 Per-Router Isolated Session:\n";
        echo "   Router ID  : " . $ros->getRouterId() . "\n";
        echo "   Session ID : " . $sess->getSessionId() . "\n";
        echo "   Token      : " . $sess->getToken() . "\n";
        echo "   Status     : " . ($sess->isValid() ? 'Active / Valid' : 'Expired') . "\n";
        echo "   Data       : " . json_encode($sess->all()) . "\n";
        break;

    case 'logs':
        $limit = (int)($args[1] ?? 10);
        $logs = $ros->getLogs($limit);
        echo "📋 Multi-Logging History (Router ID: {$ros->getRouterId()} - Max {$limit}):\n";
        echo sprintf("%-20s | %-12s | %-15s | %-30s\n", "Timestamp", "Category", "Action", "Details");
        echo "------------------------------------------------------------------------------------\n";
        foreach ($logs as $l) {
            echo sprintf("%-20s | %-12s | %-15s | %-30s\n",
                $l['timestamp'] ?? ($l['created_at'] ?? ''),
                $l['category'] ?? '',
                $l['action'] ?? '',
                substr($l['details'] ?? ($l['message'] ?? ''), 0, 30)
            );
        }
        break;

    // --- MULTI-PTMP ROUTEROS CONNECTION COMMANDS ---
    case 'ptmp':
        $sub = strtolower($args[1] ?? 'stations');
        switch ($sub) {
            case 'stations':
                $iface = $args[2] ?? null;
                $stations = $ros->ptmp()->getStations($iface);
                echo "📡 Connected PTMP Client Stations (" . count($stations) . "):\n";
                echo sprintf("%-18s | %-10s | %-10s | %-10s | %-8s | %-12s\n", "MAC Address", "Signal", "CCQ TX/RX", "Rate", "Uptime", "Health");
                echo "--------------------------------------------------------------------------------\n";
                foreach ($stations as $s) {
                    echo sprintf("%-18s | %-10s | %-10s | %-10s | %-8s | %-12s\n",
                        $s['mac_address'], $s['signal_strength'], $s['ccq_tx'], $s['tx_rate'], $s['uptime'], $s['health']);
                }
                break;

            case 'metrics':
                $metrics = $ros->ptmp()->getStationQualityMetrics($args[2] ?? null);
                echo "📊 PTMP Health Metrics:\n";
                echo "   Total Stations : " . $metrics['total_stations'] . "\n";
                echo "   Average Signal : " . $metrics['average_signal'] . "\n";
                echo "   Degraded Links : " . $metrics['degraded_count'] . "\n";
                echo "   Optimal Links  : " . $metrics['optimal_count'] . "\n";
                echo "   Health Index   : " . $metrics['health_percentage'] . "%\n";
                break;

            case 'access':
                $list = $ros->ptmp()->getAccessList();
                echo "📋 PTMP Access List Rules (" . count($list) . "):\n";
                echo json_encode($list, JSON_PRETTY_PRINT) . "\n";
                break;

            case 'kick':
                $mac = $args[2] ?? '';
                if (!$mac) {
                    echo "⚠️  Penggunaan: php console.php ptmp kick [mac_address]\n";
                    exit(1);
                }
                $res = $ros->ptmp()->kickStation($mac);
                echo !isset($res['error']) ? "✅ Station {$mac} berhasil di-kick!\n" : "❌ Gagal: {$res['error']}\n";
                break;

            case 'channel':
                $iface = $args[2] ?? '';
                $freq = $args[3] ?? '5180';
                if (!$iface) {
                    echo "⚠️  Penggunaan: php console.php ptmp channel [interface] [frequency]\n";
                    exit(1);
                }
                $res = $ros->ptmp()->setChannel($iface, $freq);
                echo !isset($res['error']) ? "✅ Channel pada {$iface} diset ke {$freq} MHz\n" : "❌ Gagal: {$res['error']}\n";
                break;

            case 'protocol':
                $iface = $args[2] ?? '';
                $proto = $args[3] ?? 'nv2';
                if (!$iface) {
                    echo "⚠️  Penggunaan: php console.php ptmp protocol [interface] [protocol]\n";
                    exit(1);
                }
                $res = $ros->ptmp()->setProtocol($iface, $proto);
                echo !isset($res['error']) ? "✅ Protokol PTMP pada {$iface} diset ke {$proto}\n" : "❌ Gagal: {$res['error']}\n";
                break;

            default:
                echo "Pilihan PTMP: php console.php ptmp [stations|metrics|access|kick|channel|protocol]\n";
                break;
        }
        break;

    // --- UNIVERSAL CRUD ACTIONS ---
    case 'create':
    case 'add':
        $path = $args[1] ?? '';
        if (!$path) {
            echo "⚠️  Penggunaan: php console.php create [path] [key1=val1] [key2=val2] ...\n";
            exit(1);
        }
        $params = [];
        foreach (array_slice($args, 2) as $pair) {
            if (str_contains($pair, '=')) {
                [$k, $v] = explode('=', $pair, 2);
                $params[$k] = $v;
            }
        }
        $res = $ros->create()->create($path, $params);
        echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
        break;

    case 'read':
    case 'get':
        $path = $args[1] ?? '';
        if (!$path) {
            echo "⚠️  Penggunaan: php console.php read [path] [optional:where_key=val]\n";
            exit(1);
        }
        $where = [];
        foreach (array_slice($args, 2) as $pair) {
            if (str_contains($pair, '=')) {
                [$k, $v] = explode('=', $pair, 2);
                $where[$k] = $v;
            }
        }
        $res = $ros->read()->read($path, $where);
        echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
        break;

    case 'terminal':
    case 'term':
        $cmd = $args[1] ?? '';
        if (!$cmd) {
            echo "⚠️  Penggunaan: php console.php terminal \"[cli_command]\"\n";
            exit(1);
        }
        $output = $ros->write()->terminal($cmd);
        echo $output . "\n";
        break;

    case 'comm':
        $cmd = $args[1] ?? '';
        if (!$cmd) {
            echo "⚠️  Penggunaan: php console.php comm \"/ip/address/print\"\n";
            exit(1);
        }
        $res = $ros->customCommand($cmd);
        echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
        break;

    case 'help':
    default:
        echo "Pilihan Perintah CLI:\n";
        echo "  php console.php status                      - Status & resource router\n";
        echo "  php console.php protocol [api|rest|ssh|telnet] - Ganti protokol koneksi\n";
        echo "  php console.php session                     - Cek token & status sesi terisolasi per-router\n";
        echo "  php console.php logs [limit]                - Multi-Logging per-router ID (DB & file)\n";
        echo "  php console.php broadcast \"[pesan]\"         - Broadcast pesan ke Multi ChatId Telegram & WhatsApp\n";
        echo "  php console.php ptmp stations               - Pantau sinyal & CPE PTMP Connection\n";
        echo "  php console.php ptmp metrics                - Ringkasan kualitas link PTMP\n";
        echo "  php console.php ptmp kick [mac]             - Kick CPE station tertentu\n";
        echo "  php console.php ptmp channel [iface] [freq] - Ganti frekuensi AP\n";
        echo "  php console.php ptmp protocol [iface] [nv2] - Ganti mode NV2/Nstreme\n";
        echo "  php console.php voucher [qty] [prof] [jam]  - Buat voucher Hotspot instan\n";
        echo "  php console.php hotspot:profile [prof] [rate] [validity] [harga] - Setup Hotspot Profile & On-Login\n";
        echo "  php console.php pppoe:profile [prof] [rate] [local] [remote]   - Setup PPPoE Customer Profile & Auto-Isolir\n";
        echo "  php console.php isolir [pool_range] [gw] [rate]               - Setup Isolir Pool, Profile & Hourly Scheduler\n";
        echo "  php console.php schedulers [install|list]                     - Pasang / cek 6 Core Automation Schedulers\n";
        echo "  php console.php setup-all                                     - 1-Klik Bootstrap Seluruh Ekosistem Otomasi\n";
        echo "  php console.php suspend [user]              - Isolir pelanggan PPPoE manual\n";
        echo "  php console.php unsuspend [user]            - Buka isolir pelanggan PPPoE\n";
        echo "  php console.php reconnect [user] [prof] [due] - Reaktivasi pelanggan pasca bayar\n";
        echo "  php console.php isolate [user] [alasan]     - Isolir pelanggan dengan alasan tercatat\n";
        echo "  php console.php kick [user]                 - Putuskan sesi user aktif\n";
        echo "  php console.php setlimit [user] [limit]     - Ubah limit uptime/bytes user\n";
        echo "  php console.php setcomment [user] [teks]    - Ubah komentar user\n";
        echo "  php console.php setname [oldName] [newName] - Ganti nama/username item\n";
        echo "  php console.php findid [name] [path]        - Cari .id dari nama item\n";
        echo "  php console.php create [path] [key=val ...] - Generic add record baru\n";
        echo "  php console.php read [path] [where=val ...] - Generic read/print record\n";
        echo "  php console.php terminal \"[command]\"        - Eksekusi Terminal CLI raw\n";
        break;
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
