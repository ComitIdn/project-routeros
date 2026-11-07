<?php
/**
 * 05 - Dedicated Operational Action Classes
 * Superpower CRUD using Finder, Setter, Getter, Printer, Remover, and Toggler.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

$ros = RouterOS::withConfig(__DIR__ . '/../config.php');

// --- 1. FINDER ($ros->find()) ---
// Resolve human name to internal RouterOS .id (e.g. '*1A')
$id = $ros->find()->nameToId('budi', '/ip/hotspot/user');
echo "🔍 Resolved 'budi' -> ID: " . ($id ?? 'not found') . "\n";

// Query by MAC address or IP
$host = $ros->find()->byMac('00:1A:2B:3C:4D:5E');
$activeSession = $ros->find()->byIp('192.168.88.254');


// --- 2. SETTER ($ros->set()) ---
// Granular setter methods
$ros->set()->limit('budi', '2h', '1G');           // Set uptime + byte quota
$ros->set()->comment('budi', 'VIP RT/RW Net');     // Set comment
$ros->set()->email('budi', 'budi@gmail.com');      // Set email
$ros->set()->name('budi', 'budi_baru');            // Rename item safely

// Set arbitrary property on any RouterOS path dynamically
$ros->set()->property('/ip/firewall/nat', '*1', 'comment', 'NAT Ether1 Internet');


// --- 3. GETTER ($ros->get()) ---
// Fetch single property without downloading whole object (Low CPU/RAM)
$uptime = $ros->get()->getValue('/ip/hotspot/user', 'budi_baru', 'uptime');
$totalUsers = $ros->get()->count('/ip/hotspot/user');
$userExists = $ros->get()->exists('/ip/hotspot/user', ['name' => 'budi_baru']);

echo "📊 Total Users: {$totalUsers} | budi_baru exists: " . ($userExists ? 'yes' : 'no') . "\n";


// --- 4. PRINTER ($ros->print()) ---
// Print only requested columns (Saves router bandwidth)
$usersSimple = $ros->print()->printColumns('/ip/hotspot/user', ['name', 'profile', 'limit-uptime']);

// Auto-pagination for dashboard tables
$page1 = $ros->print()->paginate('/ip/hotspot/user', 1, 10);
echo "📄 Paginated page 1: " . count($page1['data']) . " items of {$page1['total']} total.\n";


// --- 5. REMOVER & TOGGLER ($ros->remove() & $ros->toggle()) ---
// Toggle status on/off in one single call
$ros->toggle()->toggle('/ip/hotspot/user', 'budi_baru');

// Safe removal by name or by condition
// $ros->remove()->byName('temp_user', '/ip/hotspot/user');
// $ros->remove()->where('/ip/hotspot/user', ['comment' => 'expired']);

echo "✅ Action classes executed cleanly.\n";
