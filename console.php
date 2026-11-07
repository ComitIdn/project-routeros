<?php
/**
 * =========================================================================
 * Project RouterOS Framework - Interactive CLI Console Tool
 * =========================================================================
 *
 * Jalankan langsung dari Terminal / Termux:
 * php console.php status
 * php console.php voucher 10 default 2h
 * php console.php kick user1
 * php console.php backup mybackup
 * php console.php wol AA:BB:CC:DD:EE:FF
 * php console.php comm "/ip/address/print"
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
        echo "🖥️  Board Name      : " . ($res[0]['board-name'] ?? '-') . "\n";
        echo "⚙️  CPU Load        : " . ($res[0]['cpu-load'] ?? '0') . "%\n";
        echo "💾 Free Memory     : " . (isset($res[0]['free-memory']) ? Helper::formatBytes((float)$res[0]['free-memory']) : '-') . "\n";
        echo "⏱️  Uptime          : " . ($res[0]['uptime'] ?? '-') . "\n";
        echo "👥 Hotspot Users   : " . ($summary['total_users'] ?? 0) . "\n";
        echo "🟢 Active Sessions : " . ($summary['active_users'] ?? 0) . "\n";
        echo "📱 Detected Hosts  : " . ($summary['total_hosts'] ?? 0) . "\n";
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

        echo "✅ Berhasil membuat {$batch['generated']} voucher (Batch: {$batch['batch_tag']}):\n";
        foreach ($batch['vouchers'] as $v) {
            echo "   🔑 User: {$v['username']} | Pass: {$v['password']}\n";
        }
        break;

    case 'kick':
        $user = $args[1] ?? '';
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php kick [username]\n";
            exit(1);
        }
        $res = $ros->hotspot()->disconnectUser($user);
        echo "⚡ User '{$user}' berhasil diputuskan ({$res['disconnected']} sesi ditutup).\n";
        break;

    case 'bypass':
        $mac = $args[1] ?? '';
        $comment = $args[2] ?? 'CLI Bypassed';
        if (!Helper::isValidMac($mac)) {
            echo "⚠️  Format MAC tidak valid. Contoh: 00:11:22:33:44:55\n";
            exit(1);
        }
        $res = $ros->hotspot()->bypassDevice($mac, null, $comment);
        echo !isset($res['error']) ? "✅ Perangkat '{$mac}' berhasil di-bypass!\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'backup':
        $name = $args[1] ?? ('cli-backup-' . date('Ymd-His'));
        echo "⏳ Membuat binary backup '{$name}'...\n";
        $res = $ros->backup()->saveBackup($name);
        echo !isset($res['error']) ? "✅ Backup '{$name}.backup' berhasil dibuat di storage router!\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'wol':
        $mac = $args[1] ?? '';
        $iface = $args[2] ?? 'ether1';
        if (!Helper::isValidMac($mac)) {
            echo "⚠️  Format MAC tidak valid. Contoh: 00:11:22:33:44:55\n";
            exit(1);
        }
        $res = $ros->tool()->wakeOnLan($mac, $iface);
        echo !isset($res['error']) ? "✅ Magic packet Wake-on-LAN dikirim ke '{$mac}' via {$iface}!\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'comm':
        $cmd = $args[1] ?? '';
        if (!$cmd) {
            echo "⚠️  Penggunaan: php console.php comm \"/ip/address/print\"\n";
            exit(1);
        }
        $res = $ros->customCommand($cmd);
        echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        break;

    case 'setlimit':
        $user = $args[1] ?? '';
        $uptime = $args[2] ?? null;
        $bytes = $args[3] ?? null;
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php setlimit [username] [limit-uptime] [optional:limit-bytes]\n";
            exit(1);
        }
        $res = $ros->setLimit($user, $uptime, $bytes);
        echo !isset($res['error']) ? "✅ Limit user '{$user}' berhasil diubah (Uptime: {$uptime}, Bytes: {$bytes})\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'setcomment':
    case 'setcoment':
        $user = $args[1] ?? '';
        $comment = $args[2] ?? '';
        if (!$user) {
            echo "⚠️  Penggunaan: php console.php setcomment [username] \"komentar\"\n";
            exit(1);
        }
        $res = $ros->setComment($user, $comment);
        echo !isset($res['error']) ? "✅ Komentar user '{$user}' berhasil diubah: \"{$comment}\"\n" : "❌ Gagal: {$res['error']}\n";
        break;

    case 'setemail':
        $user = $args[1] ?? '';
        $email = $args[2] ?? '';
        if (!$user || !$email) {
            echo "⚠️  Penggunaan: php console.php setemail [username] [email]\n";
            exit(1);
        }
        $res = $ros->setEmail($user, $email);
        echo !isset($res['error']) ? "✅ Email user '{$user}' berhasil diubah: {$email}\n" : "❌ Gagal: {$res['error']}\n";
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

    case 'help':
    default:
        echo "Pilihan Perintah CLI:\n";
        echo "  php console.php status                      - Status & resource router\n";
        echo "  php console.php voucher [qty] [prof] [jam]  - Buat voucher Hotspot instan\n";
        echo "  php console.php kick [user]                 - Putuskan sesi user aktif\n";
        echo "  php console.php bypass [mac] [nama]         - Bypass perangkat tanpa login\n";
        echo "  php console.php backup [nama]               - Buat binary backup di router\n";
        echo "  php console.php wol [mac] [interface]       - Kirim Wake-on-LAN ke PC\n";
        echo "  php console.php setlimit [user] [limit]     - Ubah limit uptime/bytes user\n";
        echo "  php console.php setcomment [user] [teks]    - Ubah komentar user\n";
        echo "  php console.php setemail [user] [email]     - Ubah email user\n";
        echo "  php console.php setname [oldName] [newName] - Ganti nama/username item\n";
        echo "  php console.php findid [name] [path]        - Cari .id dari nama item\n";
        echo "  php console.php suspend [user]              - Isolir pelanggan PPPoE\n";
        echo "  php console.php unsuspend [user]            - Buka isolir pelanggan PPPoE\n";
        echo "  php console.php comm \"[perintah]\"           - Eksekusi RouterOS command\n";
        break;
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
