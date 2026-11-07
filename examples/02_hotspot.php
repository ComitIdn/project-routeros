<?php
/**
 * 02 - Hotspot Management
 * User creation, limits, comments, email, MAC locking, live traffic, and bypass.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

$ros = RouterOS::withConfig(__DIR__ . '/../config.php');

// 1. Hotspot User Management
$hotspot = $ros->hotspot();

// 1. Setup Profil Voucher ISP dengan On-Login Script Otomatis (Auto Expiry & Auto MAC Lock)
$hotspot->createVoucherProfile('1HARI-5MB', '2M/5M', '1d', [
    'shared_users' => '1',
    'lock_mac'     => true,  // Otomatis kunci ke MAC HP klien saat login pertama kali
    'track_expiry' => true,  // Hitung jatuh tempo dari waktu login pertama & auto-kick saat habis
]);

// 2. Pasang Scheduler Pembersih Sesi Kadaluarsa di MikroTik
$hotspot->installExpiryCleanupScheduler(5); // Cek setiap 5 menit

// 3. Create new user
$hotspot->addUser('budi', 'pass123', '1HARI-5MB', '1d', 'VIP Customer');

// Granular updates using dedicated setters
$hotspot->setLimit('budi', '3h', '1G');           // Update uptime & byte quota
$hotspot->setComment('budi', 'Langganan Kafe 3 Jam');
$hotspot->setEmail('budi', 'budi@gmail.com');
$hotspot->setName('budi', 'budi_vip');            // Rename user

// 4. Lock user to a specific smartphone/laptop MAC (Anti-sharing / Anti-tethering)
$hotspot->lockUserToMac('budi_vip', '00:1A:2B:3C:4D:5E');
// $hotspot->unlockUserMac('budi_vip');

// 5. Live User Traffic & Top Consumers
$traffic = $hotspot->getUserTraffic('budi_vip');
$topDownloaders = $hotspot->getTopDownloaders(5);

// 6. Device Bypass without portal login (Smart TV, CCTV, Printer)
$hotspot->bypassDevice('00:11:22:33:44:55', '192.168.88.50', 'Smart TV Lobby');

// 7. Walled Garden (Allow domain without login)
$hotspot->addWalledGardenDomain('*.whatsapp.com', 'allow');

// 8. Terminate Active Session (Kick)
$hotspot->disconnectUser('budi_vip');

echo "✅ Hotspot demo operations completed successfully.\n";
