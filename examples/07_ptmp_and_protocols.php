<?php
/**
 * Contoh 07: Multi-Protocol Transports & Multi-PTMP Wireless Management
 *
 * Mendemonstrasikan:
 * 1. Multi-Protocol Connection (API, REST v7+, SSH CLI, Telnet CLI, Auto)
 * 2. Full Actions (Creator, Writer, Reader, Finder, Setter, Remover, Toggler)
 * 3. Point-to-Multipoint (PTMP) Wireless (NV2 TDMA, Station Telemetry, Access-List)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

echo "============================================================\n";
echo "⚡ CONTOH 07: MULTI-PROTOCOL & MULTI-PTMP WIRELESS\n";
echo "============================================================\n\n";

// 1. KONEKSI FLEKSIBEL DENGAN BERBAGAI PROTOKOL
echo "--- 1. Multi-Protocol Transports ---\n";

// A. Koneksi REST Native HTTP/HTTPS (RouterOS v7+)
$rosRest = RouterOS::connect([
    'host'     => '192.168.88.1',
    'user'     => 'admin',
    'pass'     => '',
    'protocol' => 'rest', // Menggunakan port 80 / 443 REST API v7+
    'timeout'  => 1,
]);
echo "• Protocol REST Configured: " . $rosRest->getProtocol() . "\n";

// B. Koneksi SSH CLI
$rosSsh = RouterOS::connect([
    'host'     => '192.168.88.1',
    'user'     => 'admin',
    'pass'     => '',
    'protocol' => 'ssh',  // Port 22 CLI
    'timeout'  => 1,
]);
echo "• Protocol SSH Configured: " . $rosSsh->getProtocol() . "\n";

// C. Koneksi Auto-Detect (Cari port terbuka: API -> REST -> SSH -> Telnet)
$ros = RouterOS::connect([
    'host'     => '192.168.88.1',
    'user'     => 'admin',
    'pass'     => '',
    'protocol' => 'auto',
    'timeout'  => 1,
]);
echo "• Protocol Auto Configured: " . $ros->getProtocol() . "\n\n";

// 2. FULL OPERATIONAL CRUD ACTIONS (CREATOR, WRITER, READER)
echo "--- 2. Full Operational Actions (CRUD & Terminal) ---\n";

// A. Creator: Buat IP Address, Hotspot User, atau Simple Queue
/*
$ros->create()->ipAddress('192.168.100.1/24', 'ether2', 'Gateway LAN 2');
$ros->create()->hotspotUser('siswa01', 'rahasia123', 'paket-2mbps');
$ros->create()->queueSimple('Limit-Siswa', '192.168.100.0/24', '10M/10M');
*/
echo "• Creator siap digunakan: \$ros->create()->create('/path', \$data);\n";

// B. Reader: Baca data dan pantau traffic
/*
$users = $ros->read()->read('/ip/hotspot/user');
$stats = $ros->read()->stats('ether1');
*/
echo "• Reader siap digunakan: \$ros->read()->read('/path', \$where);\n";

// C. Writer: Eksekusi command, script, atau terminal raw
/*
$output = $ros->write()->terminal('/interface wireless print detail');
*/
echo "• Writer siap digunakan: \$ros->write()->terminal(\$cliCommand);\n\n";

// 3. MULTI-PTMP (POINT-TO-MULTIPOINT) WIRELESS
echo "--- 3. Multi-PTMP Wireless Management ---\n";
echo "Mendukung 802.11a/b/g/n/ac, NV2 TDMA, Nstreme, 60GHz, dan WiFiWave2/WiFi 6:\n";

/*
// Deteksi jenis hardware wireless
$hw = $ros->ptmp()->detectHardware();

// Ambil daftar station CPE yang terhubung beserta metrik sinyal TX/RX, CCQ, dan jarak
$stations = $ros->ptmp()->getStations();
foreach ($stations as $cpe) {
    echo "MAC: {$cpe['mac_address']} | Sinyal: {$cpe['signal_strength']} | CCQ: {$cpe['ccq_tx']} | Health: {$cpe['health']}\n";
}

// Ringkasan kesehatan jaringan PTMP
$metrics = $ros->ptmp()->getStationQualityMetrics();
echo "Total CPE: {$metrics['total_stations']} | Link Optimal: {$metrics['optimal_count']}\n";

// Manajemen Access-List (Whitelist / Blacklist CPE)
$ros->ptmp()->addAccessList('00:0C:42:AA:BB:CC', null, '-80..120', 'Pelanggan Tower A', true);

// Set Frekuensi & Channel Width AP Tower (bila router memiliki interface wireless/PTMP)
$ros->ptmp()->setChannel('ptmp-ap', 5200, '5ghz-only-n', '20/40mhz-XX');

// Aktifkan mode MikroTik NV2 TDMA untuk mengatasi hidden station
$ros->ptmp()->setProtocol('ptmp-ap', 'nv2');
$ros->ptmp()->setNv2Config('ptmp-ap', [
    'nv2-cell-radius'      => 30, // 30 km
    'nv2-tdma-period-size' => 2,  // 2 ms
]);
*/
echo "• PTMP Module siap: \$ros->ptmp()->getStations();\n";
echo "• PTMP Access List: \$ros->ptmp()->addAccessList(\$mac, ...);\n";
echo "• PTMP Channel & NV2: \$ros->ptmp()->setChannel(\$interface, 5180);\n\n";

echo "Semua fitur di atas juga tersedia melalui:\n";
echo "  1. CLI Terminal    : php console.php ptmp stations\n";
echo "  2. JSON REST API   : /api.php?action=ptmp_stations\n";
echo "  3. JavaScript SDK  : client.ptmp.getStations()\n";
echo "  4. Python SDK      : ros.ptmp.get_stations()\n";
echo "============================================================\n";
