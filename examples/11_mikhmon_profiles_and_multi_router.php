<?php
/**
 * Example 11: Enterprise RouterOS Automation Engine & Multi-Router Architecture
 * 
 * Demonstrasi Lengkap:
 * 1. Per-Router Isolated Session & Cache (mencegah tabrakan data multi-router).
 * 2. Multi Logging per ID RouterOS (SQL Database & File log terisolasi).
 * 3. Multi ChatId Bot Telegram & WhatsApp (Broadcast NOC & Admin alert).
 * 4. Enterprise Universal Hotspot User Profile & On-Login Script (MAC lock, auto-expire).
 * 5. Enterprise Universal PPPoE Isolir Profile, NAT Redirect & Auto-Isolir Scheduler on Due Date.
 * 6. Customer Reconnection / Reaktivasi pasca bayar.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\AutomationEngine;
use RouterOSFramework\Core\Mikhmon;

echo "====================================================================\n";
echo " Project RouterOS - Enterprise Automation Engine & Multi-Router Demo\n";
echo "====================================================================\n\n";

// Load framework
$ros = RouterOS::withConfig(__DIR__ . '/../config.php');
$ros->setRouterId('core-router-01');

// -------------------------------------------------------------------------
// 1. Per-Router Isolated Session & Cache
// -------------------------------------------------------------------------
echo "[1] Testing Per-Router Isolated Session & Cache:\n";
$session = $ros->session();
$session->set('admin_user', 'superadmin');
$session->set('dashboard_view', 'traffic_monitor');

echo "    Router ID   : " . $ros->getRouterId() . "\n";
echo "    Session ID  : " . $session->getSessionId() . "\n";
echo "    Auth Token  : " . $session->getToken() . "\n";
echo "    Session Data: " . json_encode($session->all()) . "\n";

// Per-router isolated cache
$cache = $ros->cache();
$cache->set('active_stations_summary', ['total' => 45, 'status' => 'optimal'], 300);
$cachedData = $cache->get('active_stations_summary');
echo "    Cache Data  : " . json_encode($cachedData) . "\n\n";

// -------------------------------------------------------------------------
// 2. Multi-Logging Per Router ID (DB & File Logs)
// -------------------------------------------------------------------------
echo "[2] Testing Multi-Logging per Router ID:\n";
$ros->log('BILLING', 'Due Check', 'core-router-01', 'Auto-isolir scheduler completed successfully', 'success');
$ros->log('SECURITY', 'Firewall Drop', 'core-router-01', 'Port scan detected from 192.168.88.200', 'warning');

$recentLogs = $ros->getLogs(5);
echo "    Log entries recorded for router '" . $ros->getRouterId() . "': " . count($recentLogs) . "\n";
foreach ($recentLogs as $l) {
    $cat = $l['category'] ?? ($l['module'] ?? 'SYSTEM');
    $time = $l['timestamp'] ?? ($l['created_at'] ?? date('Y-m-d H:i:s'));
    $det = $l['details'] ?? ($l['message'] ?? '');
    echo "    - [{$time}] ({$cat}) " . $l['action'] . " -> {$det}\n";
}
echo "\n";

// -------------------------------------------------------------------------
// 3. Multi ChatId Bot Telegram & WhatsApp
// -------------------------------------------------------------------------
echo "[3] Testing Multi ChatId Notification Handlers:\n";
$bot = $ros->bot();

// Dynamic addition of alert channels
$bot->addTelegramChatId('-1001234567890'); // Grup NOC
$bot->addTelegramChatId('987654321');      // Superadmin Personal
$bot->addWhatsAppRecipient('628123456789'); // WhatsApp Group NOC
$bot->addWhatsAppRecipient('628987654321'); // WhatsApp Finance

echo "    Configured Telegram Chat IDs: " . count($bot->getTelegramChatIds()) . "\n";
echo "    Configured WhatsApp Numbers : " . count($bot->getWhatsAppRecipients()) . "\n";

// Simulate alert dispatch
$alertDispatch = $bot->broadcast("🚨 [ALERT] Router " . $ros->getRouterId() . ": High CPU usage 88% detected!");
echo "    Broadcast Result: Telegram sent=" . ($alertDispatch['telegram']['sent'] ?? 0) . ", WhatsApp sent=" . ($alertDispatch['whatsapp']['sent'] ?? 0) . "\n\n";

// -------------------------------------------------------------------------
// 4. Global Hotspot User Profiles (V15D, V24h & V30D)
// -------------------------------------------------------------------------
echo "[4] Global Hotspot User Profiles & Scripts:\n";

// Example A: Profile "V15D" (address-pool hs-pool-22, add-mac-cookie=yes, 3M/2M, 15d, Rp 0 / Rp 10.000, ntfc/N)
$v15dCli = $ros->hotspot()->generateProfileCliCommand(
    'V15D',
    '3M/2M',
    '15d',
    0,
    10000,
    'ntfc',
    'Disable',
    'none',
    'hs-pool-22',
    '1',
    [
        'session_id'          => '2608001',
        'pool_ranges'         => '175.12.0.1-175.12.10.0, 175.12.10.2-175.12.255.254',
        'add_mac_cookie'      => 'yes',
        'mac_cookie_timeout'  => '1d 00:00:00',
        'insert_queue_before' => 'bottom',
        'tag'                 => 'routeros',
    ]
);

echo "    [A] Profile 'V15D' (MAC-Cookie, Pool hs-pool-22, Mode ntfc / N, Jual Rp 10.000):\n";
echo "        CLI Add : " . substr($v15dCli, 0, 120) . " ...\n\n";

// Example B: Profile "V24h" (3M/2M, 24h, Rp 8.000 / Rp 10.000, Mode remc/R, pool none)
$v24hScript = $ros->hotspot()->generateOfficialOnLoginScript(
    'V24h',
    '24h',
    8000,
    10000,
    'remc',
    'Disable',
    ['session_id' => '2312005', 'tag' => 'routeros']
);
$v24hCli = $ros->hotspot()->generateProfileCliCommand(
    'V24h',
    '3M/2M',
    '24h',
    8000,
    10000,
    'remc',
    'Disable',
    'none',
    'none',
    '1',
    ['session_id' => '2312005', 'tag' => 'routeros']
);

echo "    [B] Profile 'V24h' (Mode remc / R, Rp 8.000, Jual Rp 10.000):\n";
echo "        CLI Add : " . substr($v24hCli, 0, 105) . " ...\"\n";
echo "        Script  : " . substr($v24hScript, 0, 115) . " ...\n\n";

// Example C: Profile "V30D" (3M/2M, 30d, Rp 0 / Rp 150.000, Mode ntfc/N, pool none)
$v30dScript = $ros->hotspot()->generateOfficialOnLoginScript(
    'V30D',
    '30d',
    0,
    150000,
    'ntfc',
    'Disable',
    ['session_id' => '2608001', 'tag' => 'routeros']
);
$v30dCli = $ros->hotspot()->generateProfileCliCommand(
    'V30D',
    '3M/2M',
    '30d',
    0,
    150000,
    'ntfc',
    'Disable',
    'none',
    'none',
    '1',
    ['session_id' => '2608001', 'tag' => 'routeros']
);

echo "    [C] Profile 'V30D' (Mode ntfc / N, Rp 0, Jual Rp 150.000):\n";
echo "        CLI Add : " . substr($v30dCli, 0, 105) . " ...\"\n";
echo "        Script  : " . substr($v30dScript, 0, 115) . " ...\n\n";

// List available pools & queues for dropdown selection
$availablePools = $ros->hotspot()->getPoolList();
$availableQueues = $ros->hotspot()->getParentQueueList();
echo "    [D] Available Options for UI Dropdowns:\n";
echo "        Address Pools : " . json_encode($availablePools) . "\n";
echo "        Parent Queues : " . json_encode($availableQueues) . "\n\n";

// -------------------------------------------------------------------------
// 5. Global PPPoE Profiles & Isolir Infrastructure
// -------------------------------------------------------------------------
echo "[5] Global PPPoE Customer & Isolir Profiles:\n";
$pppoeCli = $ros->pppoe()->generatePppCliCommands(
    'P3',
    '3M/3M',
    '192.168.100.1',
    'isolir',
    '30d'
);
echo "    PPPoE Complete CLI Setup (P3 + isolir Profile + isolir Pool + isolir Scheduler):\n";
$lines = explode("\n", trim($pppoeCli));
foreach (array_slice($lines, 0, 8) as $l) {
    echo "      > " . (strlen($l) > 100 ? substr($l, 0, 97) . '...' : $l) . "\n";
}
echo "      > ... (Total " . strlen($pppoeCli) . " bytes of RouterOS CLI configuration)\n\n";

// -------------------------------------------------------------------------
// 6. Complete Schedulers Registry (Universal Automation Engine)
// -------------------------------------------------------------------------
echo "[6] Universal Automation Engine Schedulers (All 6 Schedulers):\n";
$allScheds = AutomationEngine::getAllSchedulers();
foreach ($allScheds as $sName => $sCfg) {
    echo "    - [{$sName}]\n";
    echo "      Interval  : {$sCfg['interval']}\n";
    echo "      Start-Time: {$sCfg['start-time']}\n";
    echo "      Comment   : {$sCfg['comment']}\n";
    echo "      Script Len: " . strlen($sCfg['on-event']) . " bytes\n";
}
echo "\n";

// -------------------------------------------------------------------------
// 7. PPPoE Customer Due Date & Reconnection Flow
// -------------------------------------------------------------------------
echo "[7] PPPoE Customer Billing & Reconnection Flow:\n";
echo "    - Step 1: Customer secret created with comment 'lunas Paket 30 Hari'\n";
echo "    - Step 2: Customer logs in -> Profile P3 on-up script sets comment '\$exp | \$komen'\n";
echo "    - Step 3: Hourly scheduler 'isolir' checks expiration against clock\n";
echo "    - Step 4: If overdue -> profile shifted to 'isolir', active kicked, comment marked 'BELUM BAYAR'\n";
echo "    - Step 5: Customer pays bill -> comment reset to 'lunas ...' and profile shifted back to 'P3'\n\n";

echo "====================================================================\n";
echo " Automation Engine & Multi-Router Demo Successfully Finished!\n";
echo "====================================================================\n";
