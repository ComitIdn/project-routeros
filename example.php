<?php
/**
 * =========================================================================
 * Project RouterOS PHP Framework - Master Example & Reference Guide
 * =========================================================================
 *
 * Professional, clean reference demonstrating modern MikroTik RouterOS
 * management with Multi-Protocol Transports (API, REST, SSH, Telnet),
 * Full Operational Actions (CRUD), Point-to-Multipoint (PTMP) Wireless,
 * SQL Database, and JSON Flat-File storage.
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\RouterOSAPI;

// 1. Initialize Framework using config.php
$ros = RouterOS::withConfig('config.php');

echo "⚡ Project RouterOS Framework Loaded Successfully\n";
echo "🌐 Active Protocol: " . strtoupper($ros->getProtocol()) . "\n";

if (!$ros->isConnected()) {
    echo "ℹ️  Status Router: Offline (Sesuaikan IP & akun router pada 'config.php' untuk pengujian live)\n";
    echo "📚 Contoh sintaks siap pakai berikut dapat langsung digunakan pada aplikasi Anda:\n\n";
}

/*
|--------------------------------------------------------------------------
| 1. FULL OPERATIONAL ACTIONS (Creator, Writer, Reader, Finder, Setter, Getter, Remover, Toggler)
|--------------------------------------------------------------------------
| File class terpisah di src/Actions/ yang bersih, modular, tanpa dobel nama.
*/

if ($ros->isConnected()) {
    // A. Creator: Buat IP, User Hotspot, Simple Queue secara terstruktur
    $ros->create()->ipAddress('192.168.100.1/24', 'ether2', 'Gateway LAN 2');
    $ros->create()->queueSimple('Limit-Kantor', '192.168.100.0/24', '20M/20M');

    // B. Finder: Temukan ID dari nama atau kriteria
    $id = $ros->findNameToId('budi', '/ip/hotspot/user');

    // C. Setter: Ubah limit, komentar, email, nama (rename)
    $ros->setLimit('budi', '2h', '1G');           // Set uptime + byte limit
    $ros->setComment('budi', 'Pelanggan VIP');     // Set comment
    $ros->setEmail('budi', 'budi@gmail.com');      // Set email
    $ros->setName('budi', 'budi_baru');            // Rename item aman

    // D. Reader & Getter: Paginasi dan seleksi data
    $totalUsers = $ros->get()->count('/ip/hotspot/user');
    $page1      = $ros->print()->paginate('/ip/hotspot/user', 1, 10);

    // E. Writer: Eksekusi command raw atau Terminal CLI
    $termOutput = $ros->write()->terminal('/interface wireless print detail');

    // F. Toggler: Toggle status on/off dalam 1 baris
    $ros->toggle()->toggle('/ip/hotspot/user', 'budi_baru');
} else {
    echo "🔹 [Action Creator] \$ros->create()->ipAddress('192.168.100.1/24', 'ether2');\n";
    echo "🔹 [Action Creator] \$ros->create()->queueSimple('Limit-Kantor', '192.168.100.0/24', '20M/20M');\n";
    echo "🔹 [Action Setter]  \$ros->setLimit('budi', '2h', '1G');\n";
    echo "🔹 [Action Setter]  \$ros->setComment('budi', 'VIP RT/RW Net');\n";
    echo "🔹 [Action Setter]  \$ros->setEmail('budi', 'budi@gmail.com');\n";
    echo "🔹 [Action Setter]  \$ros->setName('budi', 'budi_baru');\n";
    echo "🔹 [Action Finder]  \$id = \$ros->findNameToId('budi');\n";
    echo "🔹 [Action Reader]  \$data = \$ros->read()->read('/ip/hotspot/user');\n";
    echo "🔹 [Action Writer]  \$output = \$ros->write()->terminal('/system resource print');\n";
}

/*
|--------------------------------------------------------------------------
| 2. MULTI-PROTOCOL TRANSPORTS (API, REST v7+, SSH CLI, TELNET CLI, AUTO)
|--------------------------------------------------------------------------
| Bebas dependensi monoton: bisa ganti protokol sewaktu-waktu.
*/
echo "\n--- Multi-Protocol Transports ---\n";
echo "🔹 [REST Native v7]  \$ros->useRest();   // Port 80/443 Native HTTP/HTTPS JSON\n";
echo "🔹 [SSH Terminal]    \$ros->useSsh();    // Port 22 SSH CLI\n";
echo "🔹 [Telnet Socket]   \$ros->useTelnet(); // Port 23 Raw Socket Telnet\n";
echo "🔹 [API Socket]      \$ros->useApi();    // Port 8728 RouterOS API Binary\n";
echo "🔹 [Auto Detect]     \$ros->useAuto();   // Auto-detect port terbuka\n";

/*
|--------------------------------------------------------------------------
| 3. MULTI-PTMP (POINT-TO-MULTIPOINT) WIRELESS MANAGEMENT
|--------------------------------------------------------------------------
| Mendukung 802.11, NV2 TDMA, Nstreme, 60GHz (w60g), dan WiFiWave2/WiFi 6.
*/
echo "\n--- Multi-PTMP Routeros Connection Management ---\n";
echo "🔹 [PTMP Stations]    \$stations = \$ros->ptmp()->getStations();\n";
echo "🔹 [PTMP Metrics]     \$metrics  = \$ros->ptmp()->getStationQualityMetrics();\n";
echo "🔹 [PTMP Access-List] \$ros->ptmp()->addAccessList('00:0C:42:AA:BB:CC', null, '-80..120');\n";
echo "🔹 [PTMP Kick CPE]    \$ros->ptmp()->kickStation('00:0C:42:AA:BB:CC');\n";
echo "🔹 [PTMP Set Channel] \$ros->ptmp()->setChannel(\$apInterface, 5180, '5ghz-only-n');\n";
echo "🔹 [PTMP Set NV2]     \$ros->ptmp()->setProtocol(\$apInterface, 'nv2');\n";

/*
|--------------------------------------------------------------------------
| 4. HOTSPOT & VOUCHER ENGINE (MASS SCALE UP TO 10.000 VOUCHERS)
|--------------------------------------------------------------------------
| On-login auto-expiry, MAC lock, single-roundtrip CLI script push, dan batch SQL insert.
*/

if ($ros->isConnected()) {
    // A. Buat profil Hotspot dengan On-Login script otomatis (Auto Expiry & Auto MAC Lock)
    $ros->hotspot()->createVoucherProfile('1HARI-5MB', '2M/5M', '1d', [
        'shared_users' => '1',
        'lock_mac'     => true,
        'track_expiry' => true,
    ]);

    // B. Bulk Fast Generate 100 voucher langsung ke Database & RouterOS
    $batch = $ros->voucher()->generateAndSave(100, [
        'prefix'       => 'VC-',
        'profile'      => '1HARI-5MB',
        'limit_uptime' => '1d',
        'price'        => 5000,
        'batch_tag'    => 'demo-mass-' . date('Ymd'),
    ]);
    echo "✅ Berhasil generate {$batch['generated']} voucher Hotspot dalam {$batch['duration_ms']} ms\n";
} else {
    echo "\n--- Hotspot & Voucher Engine (Mass-Scale) ---\n";
    echo "🔹 [On-Login Profile] \$ros->hotspot()->createVoucherProfile('1HARI-5MB', '2M/5M', '1d');\n";
    echo "🔹 [Fast Bulk 10.000] \$ros->voucher()->generateFastBulk(10000, ['profile' => '1HARI', 'price' => 5000]);\n";
    echo "🔹 [Hotspot Kick]     \$ros->hotspot()->kickUser('budi');\n";
}

/*
|--------------------------------------------------------------------------
| 5. PPPOE ISP MANAGEMENT & AUTO ISOLATION
|--------------------------------------------------------------------------
*/

if ($ros->isConnected()) {
    $ros->pppoe()->setPppoeDueDate('client01', '2026-11-20');
    $isolated = $ros->pppoe()->autoIsolateOverdue('ISOLIR');
    $ros->pppoe()->unsuspendCustomer('client01', 'default');
} else {
    echo "\n--- PPPoE ISP Billing ---\n";
    echo "🔹 [PPPoE Billing] \$ros->pppoe()->setPppoeDueDate('client01', '2026-11-20');\n";
    echo "🔹 [PPPoE Billing] \$ros->pppoe()->autoIsolateOverdue('ISOLIR');\n";
    echo "🔹 [PPPoE Billing] \$ros->pppoe()->unsuspendCustomer('client01', 'default');\n";
}

/*
|--------------------------------------------------------------------------
| 6. ENTERPRISE SERVER INTERFACES & FIREWALL HARDENING
|--------------------------------------------------------------------------
| Dioptimalkan untuk Server MikroTik Enterprise (CCR, CRS, CHR, x86).
*/
echo "\n--- Enterprise Server Interfaces & Security Hardening ---\n";
echo "🔹 [SFP / SFP+ Fiber]     \$ros->interface()->getSfp();\n";
echo "🔹 [LACP Bonding 802.3ad] \$ros->interface()->getBonding();\n";
echo "🔹 [VRRP HA Cluster]      \$ros->interface()->getVrrp();\n";
echo "🔹 [FastTrack Accelerate] \$ros->firewall()->enableFastTrack();\n";
echo "🔹 [Brute-Force Guard]    \$ros->firewall()->protectBruteForce([22, 8291, 8728, 8729]);\n";
echo "🔹 [1-Click Hardening]    \$ros->firewall()->hardenServerBaseline('bridge', 'ether1');\n";

/*
|--------------------------------------------------------------------------
| 7. DATABASE SQL (SQLite & MySQL) & JSON FLAT-FILE
|--------------------------------------------------------------------------
| Transaksi ACID, chunked batch insert multi-baris, dan indexing O(log N).
*/
echo "\n--- Unified Database Engine (SQLite & MySQL) ---\n";
echo "🔹 [Batch Multi-Row]  \$ros->db()->insertBatch('vouchers', \$vouchersList, 500);\n";
echo "🔹 [ACID Transaction] \$ros->db()->transaction(function(\$db) { ... });\n";
echo "🔹 [Database Stats]   print_r(\$ros->db()->stats());\n";

// Flat-File JSON Storage
$json = $ros->json();
$json->set('app_config', 'site_name', 'Hotspot & PTMP RT/RW Net');
$json->push('transactions', [
    'invoice' => 'INV-001',
    'amount'  => 5000,
    'date'    => date('Y-m-d H:i:s'),
]);
echo "\n✅ Flat-File JSON Storage Ready (database/json/app_config.json)\n";
echo "🚀 RouterOS Version Detector Ready (v6 / v7 auto-adaptation)\n";
echo "📂 Multi-Language SDK: Python ('routeros.py'), JS ('assets/js/routeros.js'), REST API ('api.php'), CLI ('console.php')\n";
echo "📂 Lihat contoh modular lengkap di: 'examples/'\n";
