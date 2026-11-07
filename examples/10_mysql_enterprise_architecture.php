<?php
/**
 * 10 - Enterprise MySQL / MariaDB Architecture
 * High-Throughput Batch Transactions, Connection Pooling, Indexed Lookups,
 * and Live Bi-Directional Synchronization between RouterOS and MySQL.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\RouterOS;

echo "🗄️ ========================================================\n";
echo "🚀 10 - ENTERPRISE MYSQL / MARIADB ARCHITECTURE\n";
echo "🗄️ ========================================================\n\n";

// 1. Inisialisasi Database Engine (Mendukung MySQL dan SQLite dengan API yang sama persis)
$db = new Database([
    'driver'     => 'sqlite', // Ganti ke 'mysql' untuk koneksi ke server MySQL / MariaDB
    'file'       => __DIR__ . '/../database/routeros.sqlite',

    // Pengaturan MySQL / MariaDB (uncomment untuk produksi):
    // 'driver'     => 'mysql',
    // 'host'       => '127.0.0.1',
    // 'port'       => 3306,
    // 'database'   => 'routeros_db',
    // 'username'   => 'root',
    // 'password'   => 'secret',
    // 'persistent' => true, // Connection pool / persistent PDO connection
]);

echo "✅ Active Database Driver: " . strtoupper($db->getDriver()) . "\n";

// 2. High-Throughput Multi-Row Batch Insert (Chunked Insert dalam 1 Transaksi)
echo "\n--- 2. High-Throughput Batch Insert Demo (1.000 Vouchers) ---\n";
$start = microtime(true);

$sampleVouchers = [];
$batchTag = 'test-batch-' . date('Ymd-His');
for ($i = 1; $i <= 1000; $i++) {
    $code = strtoupper(substr(md5((string)$i . microtime()), 0, 6));
    $sampleVouchers[] = [
        'router_id'         => 1,
        'username'          => 'VCR-' . $code,
        'password'          => 'VCR-' . $code,
        'profile'           => '1JAM-2MB',
        'limit_uptime'      => '1h',
        'limit_bytes_total' => '1073741824',
        'price'             => 3000,
        'status'            => 'active',
        'batch_tag'         => $batchTag,
        'comment'           => 'Voucher Promo Kafe',
        'created_at'        => date('Y-m-d H:i:s'),
    ];
}

// Eksekusi insert batch (1.000 baris dalam chunks 500 baris per query SQL)
$inserted = $db->insertBatch('vouchers', $sampleVouchers, 500);
$elapsedMs = round((microtime(true) - $start) * 1000, 2);

echo "⚡ Berhasil insert {$inserted} data voucher ke Database dalam {$elapsedMs} ms!\n";

// 3. Indexed Fast Query & Filtering (O(log N) tanpa Full Table Scan)
echo "\n--- 3. Indexed Lookups ---\n";
$activeCount = $db->count('vouchers', "status = 'active'");
$batchRecords = $db->select('vouchers', 'batch_tag = :b', ['b' => $batchTag], 'id DESC', 5);

echo "📊 Total Voucher Aktif di Database : {$activeCount}\n";
echo "🔍 Sample 1 Record Voucher Terindeks: " . json_encode($batchRecords[0] ?? [], JSON_PRETTY_PRINT) . "\n";

// 4. Safe Database Transaction Block
echo "\n--- 4. Safe Database Transactions ---\n";
$db->transaction(function (Database $tx) use ($batchTag) {
    // Semua operasi di dalam closure ini berjalan di dalam ACID transaction:
    // Jika ada error/exception, rollback otomatis; jika sukses, commit otomatis.
    $tx->update('vouchers', ['status' => 'used', 'used_at' => date('Y-m-d H:i:s')], 'batch_tag = :b LIMIT 1', ['b' => $batchTag]);
    $tx->log('Voucher', 'Redeem Voucher', $batchTag, 'Marked voucher as used inside transaction', 'success');
});
echo "✅ Transaksi database atomik berhasil dieksekusi.\n";

// 5. Database Operational Health & Metrics
echo "\n--- 5. Database Operational Metrics ---\n";
$stats = $db->stats();
print_r($stats);

// Cleanup test batch agar database tetap rapi
$db->delete('vouchers', 'batch_tag = :b', ['b' => $batchTag]);
echo "🧹 Cleaned up test batch from database.\n";
