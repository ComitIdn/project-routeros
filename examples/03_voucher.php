<?php
/**
 * 03 - Bulk Voucher Engine
 * Bulk generation, SQL/JSON storage, QR Code auto-login, print cards, thermal printer receipt, sales reports.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

$ros = RouterOS::withConfig(__DIR__ . '/../config.php');
$voucher = $ros->voucher();

// 1. Bulk Voucher Generation (Pushed to RouterOS & Saved to SQL Database)
$batch = $voucher->generateAndSave(10, [
    'prefix'            => 'VC-',
    'length'            => 6,
    'characters'        => 'alphanumeric',
    'user_mode'         => 'user_password', // Username equals password
    'profile'           => 'default',
    'limit-uptime'      => '2h',
    'limit-bytes-total' => '1073741824',    // 1 GB
    'price'             => 5000,
    'batch_tag'         => 'kafe-' . date('Ymd'),
]);

echo "✅ Generated {$batch['generated']} vouchers with batch tag: {$batch['batch_tag']}\n";

// 1B. High-Throughput Engine Benchmark (Simulasi 5.000 s/d 10.000 Voucher Massal)
$startBench = microtime(true);
$bulkBatch = $voucher->generateFastBulk(2000, [
    'prefix'       => 'MASS-',
    'length'       => 6,
    'characters'   => 'alphanumeric_upper',
    'profile'      => '1HARI-5MB',
    'limit_uptime' => '1d',
    'price'        => 5000,
    'batch_tag'    => 'mass-event-' . date('Ymd'),
    'sync_mode'    => 'stock_only', // Disimpan ke SQL stock inventory, super cepat tanpa membebani CPU router
    'save_db'      => true,
]);
$benchMs = round((microtime(true) - $startBench) * 1000, 2);
echo "⚡ [Benchmark Massal] Berhasil generate & save {$bulkBatch['generated']} voucher ke Database dalam {$benchMs} ms!\n";

// 2. Fetch vouchers from database
$vouchersInDb = $voucher->getFromDb(['batch_tag' => $batch['batch_tag']]);

// 3. Render Printable Cards with QR Code Auto-Login (HTML)
$htmlCards = $voucher->renderPrintCards($vouchersInDb, [
    'title'    => 'WARKOP CYBER FREE WI-FI',
    'dns_name' => 'http://wifi.kafe/login',
    'price'    => 'Rp 5.000 / 2 Jam',
    'show_qr'  => true, // Embeds scan-to-login QR Code
]);
// file_put_contents(__DIR__ . '/voucher_cards.html', $htmlCards);

// 4. Render Bluetooth Thermal Receipt (58mm / 80mm ESC/POS format)
$thermalReceipt = $voucher->renderThermalReceipt($vouchersInDb[0] ?? [], [
    'store_name' => 'WARKOP CYBER CAFE',
    'address'    => 'Jl. Thamrin No. 45',
    'login_url'  => 'http://wifi.kafe',
]);
// echo $thermalReceipt;

// 5. Sales & Financial Reports
$sales = $voucher->getSalesReport();
echo "📊 Total Vouchers : {$sales['total_vouchers']}\n";
echo "💰 Potential Omset: {$sales['potential_human']}\n";
echo "💵 Realized Omset : {$sales['realized_human']}\n";

// 6. Export to CSV (Excel compatible)
$csv = $voucher->exportToCsv($batch['batch_tag'], __DIR__ . '/../database/exports/vouchers.csv');
echo "📄 Exported vouchers to database/exports/vouchers.csv\n";
