<?php
/**
 * =========================================================================
 * Project RouterOS PHP Framework - Master Example & Reference Guide
 * =========================================================================
 *
 * Professional, clean reference demonstrating modern MikroTik RouterOS
 * management with SQL Database, JSON storage, and dedicated action classes.
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\RouterOSAPI;

// 1. Initialize Framework using config.php
$ros = RouterOS::withConfig('config.php');

echo "⚡ Project RouterOS Framework Loaded Successfully\n";

if (!$ros->isConnected()) {
    echo "ℹ️  Status Router: Offline (Sesuaikan IP & akun router pada 'config.php' untuk pengujian live)\n";
    echo "📚 Contoh sintaks siap pakai berikut dapat langsung digunakan pada aplikasi Anda:\n\n";
}

/*
|--------------------------------------------------------------------------
| 1. DEDICATED SUPERPOWER ACTIONS (Finder, Setter, Getter, Printer)
|--------------------------------------------------------------------------
| File class terpisah di src/Actions/ yang bersih, tanpa nama dobel.
*/

if ($ros->isConnected()) {
    // A. Finder: Temukan ID dari nama atau kriteria
    $id = $ros->findNameToId('budi', '/ip/hotspot/user');

    // B. Setter: Ubah limit, komentar, email, nama (rename)
    $ros->setLimit('budi', '2h', '1G');           // Set uptime + byte limit
    $ros->setComment('budi', 'Pelanggan VIP');     // Set comment
    $ros->setEmail('budi', 'budi@gmail.com');      // Set email
    $ros->setName('budi', 'budi_baru');            // Rename item aman

    // C. Getter & Printer: Paginasi dan seleksi kolom spesifik
    $totalUsers = $ros->get()->count('/ip/hotspot/user');
    $page1      = $ros->print()->paginate('/ip/hotspot/user', 1, 10);

    // D. Toggler: Toggle status on/off dalam 1 baris
    $ros->toggle()->toggle('/ip/hotspot/user', 'budi_baru');
} else {
    echo "🔹 [Action Setter] \$ros->setLimit('budi', '2h', '1G');\n";
    echo "🔹 [Action Setter] \$ros->setComment('budi', 'VIP RT/RW Net');\n";
    echo "🔹 [Action Setter] \$ros->setEmail('budi', 'budi@gmail.com');\n";
    echo "🔹 [Action Setter] \$ros->setName('budi', 'budi_baru');\n";
    echo "🔹 [Action Finder] \$id = \$ros->findNameToId('budi');\n";
}

/*
|--------------------------------------------------------------------------
| 2. HOTSPOT & VOUCHER ENGINE
|--------------------------------------------------------------------------
*/

if ($ros->isConnected()) {
    // Pantau trafik RX/TX realtime user hotspot
    $traffic = $ros->hotspot()->getUserTraffic('budi_baru');

    // Kunci user ke MAC HP pelanggan (Anti login ganda / anti tethering)
    $ros->hotspot()->lockUserToMac('budi_baru', '00:1A:2B:3C:4D:5E');

    // Bypass Smart TV tanpa login portal
    $ros->hotspot()->bypassDevice('00:11:22:33:44:55', '192.168.88.50', 'Smart TV Lobby');
}

// Generate 10 Voucher sekaligus ke MikroTik & Database SQL
if ($ros->isConnected() && $ros->hasDatabase()) {
    $batch = $ros->voucher()->generateAndSave(10, [
        'prefix'            => 'VC-',
        'length'            => 6,
        'profile'           => 'default',
        'limit-uptime'      => '2h',
        'price'             => 5000,
        'batch_tag'         => 'kafe-' . date('Ymd'),
    ]);

    // Buat link QR Code auto-login
    $qrUrl = $ros->voucher()->generateQrCodeUrl('VC-8k2j9a', 'pass123', 'http://wifi.kafe/login');

    // Format struk thermal Bluetooth printer kasir (58mm/80mm ESC/POS)
    $struk = $ros->voucher()->renderThermalReceipt($batch['vouchers'][0] ?? [], [
        'store_name' => 'WARKOP CYBER CAFE',
        'address'    => 'Jl. Thamrin No. 45',
        'login_url'  => 'http://wifi.kafe',
    ]);
    echo "✅ Voucher Engine & Database SQL Active!\n";
} else {
    echo "🔹 [Voucher Engine] \$batch = \$ros->voucher()->generateAndSave(10, ['profile' => 'default', 'limit-uptime' => '2h']);\n";
    echo "🔹 [Voucher QR]     \$qrUrl = \$ros->voucher()->generateQrCodeUrl('VC-8k2j9a', 'pass123');\n";
    echo "🔹 [Voucher Print]  \$html  = \$ros->voucher()->renderPrintCards(\$vouchers, ['show_qr' => true]);\n";
}

/*
|--------------------------------------------------------------------------
| 3. PPPOE & ISP BILLING AUTOMATION
|--------------------------------------------------------------------------
*/

if ($ros->isConnected()) {
    // Tambah pelanggan PPPoE & tetapkan IP Statis
    $ros->pppoe()->addUser('client01', 'pass123', 'pppoe', 'default');
    $ros->pppoe()->assignStaticIp('client01', '10.10.10.50', '10.10.10.1');

    // Kunci akun PPPoE ke MAC ONT/Modem pelanggan (Anti kloning)
    $ros->pppoe()->bindCallerId('client01', '00:E0:4C:53:44:58');

    // Catat tanggal jatuh tempo tagihan (Due Date)
    $ros->pppoe()->setPppoeDueDate('client01', date('Y-m-d', strtotime('+30 days')), 'INV-2026-001');

    // Isolir massal otomatis pelanggan menunggak (Cron Job Ready)
    $overdues = $ros->pppoe()->getOverdueCustomers();
    $isolated = $ros->pppoe()->autoIsolateOverdue('ISOLIR');

    // Buka isolir saat pelanggan sudah bayar
    $ros->pppoe()->unsuspendCustomer('client01', 'default');
} else {
    echo "🔹 [PPPoE Billing] \$ros->pppoe()->setPppoeDueDate('client01', '2026-11-20');\n";
    echo "🔹 [PPPoE Billing] \$ros->pppoe()->autoIsolateOverdue('ISOLIR');\n";
    echo "🔹 [PPPoE Billing] \$ros->pppoe()->unsuspendCustomer('client01', 'default');\n";
}

/*
|--------------------------------------------------------------------------
| 4. DATABASE SQL (SQLite & MySQL) & JSON FLAT-FILE
|--------------------------------------------------------------------------
*/

if ($ros->hasDatabase()) {
    $vouchersInDb = $ros->voucher()->getFromDb(['status' => 'active', 'limit' => 20]);
    $salesReport  = $ros->voucher()->getSalesReport();
}

// Flat-File JSON Storage
$json = $ros->json();
$json->set('app_config', 'site_name', 'Hotspot RT/RW Net');
$json->push('transactions', [
    'invoice' => 'INV-001',
    'amount'  => 5000,
    'date'    => date('Y-m-d H:i:s'),
]);
echo "✅ Flat-File JSON Storage Ready (database/json/app_config.json)\n";

/*
|--------------------------------------------------------------------------
| 5. ROUTEROS v6.xx - v7.xx DETECTOR
|--------------------------------------------------------------------------
*/
$system = $ros->system();
echo "🚀 RouterOS Version Detector Ready (v6 / v7 auto-adaptation)\n";
echo "📂 Lihat contoh modular lengkap di: 'examples/'\n";
