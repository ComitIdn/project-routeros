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

// Create new user
$hotspot->addUser('budi', 'pass123', 'default', '2h', 'VIP Customer');

// Granular updates using dedicated setters
$hotspot->setLimit('budi', '3h', '1G');           // Update uptime & byte quota
$hotspot->setComment('budi', 'Langganan Kafe 3 Jam');
$hotspot->setEmail('budi', 'budi@gmail.com');
$hotspot->setName('budi', 'budi_vip');            // Rename user

// 2. Lock user to a specific smartphone/laptop MAC (Anti-sharing / Anti-tethering)
$hotspot->lockUserToMac('budi_vip', '00:1A:2B:3C:4D:5E');
// $hotspot->unlockUserMac('budi_vip');

// 3. Live User Traffic & Top Consumers
$traffic = $hotspot->getUserTraffic('budi_vip');
$topDownloaders = $hotspot->getTopDownloaders(5);

// 4. Device Bypass without portal login (Smart TV, CCTV, Printer)
$hotspot->bypassDevice('00:11:22:33:44:55', '192.168.88.50', 'Smart TV Lobby');

// 5. Walled Garden (Allow domain without login)
$hotspot->addWalledGardenDomain('*.whatsapp.com', 'allow');

// 6. Terminate Active Session (Kick)
$hotspot->disconnectUser('budi_vip');

echo "✅ Hotspot demo operations completed successfully.\n";
