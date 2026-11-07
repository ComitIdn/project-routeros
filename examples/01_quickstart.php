<?php
/**
 * 01 - Quickstart & Connection
 * Connect to MikroTik RouterOS, check resources, identity, ping, and version.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

// 1. Initialize with config.php
$ros = RouterOS::withConfig(__DIR__ . '/../config.php');

if (!$ros->isConnected()) {
    echo "❌ Connection failed: " . $ros->getConnectionError() . "\n";
    exit(1);
}

echo "✅ Connected to MikroTik RouterOS!\n";

// 2. Router Identity & System Resources
$identity  = $ros->system()->getIdentity();
$resources = $ros->system()->getResources();
$system    = $ros->system();

echo "📡 Router Name : " . ($identity[0]['name'] ?? 'MikroTik') . "\n";
echo "🖥️  Board       : " . ($resources[0]['board-name'] ?? '-') . "\n";
echo "⚙️  CPU Load    : " . ($resources[0]['cpu-load'] ?? 0) . "%\n";
echo "⏱️  Uptime      : " . ($resources[0]['uptime'] ?? '-') . "\n";
echo "🚀 Version     : " . $system->getVersion() . " (RouterOS v" . $system->getMajorVersion() . ")\n";

// 3. Ping Test
echo "\n🌐 Testing Ping to 8.8.8.8...\n";
$ping = $ros->system()->ping('8.8.8.8', 2);
foreach ($ping as $p) {
    echo "   Reply from {$p['host']}: time={$p['time']} status=" . ($p['status'] ?? 'ok') . "\n";
}
