<?php
/**
 * Example 11: Mikhmon Official Profiles & Multi-Router Architecture
 * 
 * Demonstrasi Lengkap:
 * 1. Per-Router Isolated Session & Cache (mencegah tabrakan data multi-router).
 * 2. Multi Logging per ID RouterOS (SQL Database & File log terisolasi).
 * 3. Multi ChatId Bot Telegram & WhatsApp (Broadcast NOC & Admin alert).
 * 4. Mikhmon Official Hotspot User Profile & On-Login Script (MAC lock, auto-expire).
 * 5. Mikhmon Official PPPoE Isolir Profile, NAT Redirect & Auto-Isolir Scheduler on Due Date.
 * 6. Customer Reconnection / Reaktivasi pasca bayar.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

echo "====================================================================\n";
echo " Project RouterOS - Mikhmon Profiles & Multi-Router Demo\n";
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
// 4. Mikhmon Official Hotspot On-Login & Profile Generation
// -------------------------------------------------------------------------
echo "[4] Mikhmon Official Hotspot User Profile & On-Login Script:\n";
$onLoginScript = $ros->hotspot()->generateOnLoginScript('1d', [
    'lock_mac'         => true,
    'track_expiry'     => true,
    'expiry_mode'      => 'remove',
    'price'            => 5000,
    'telegram_token'   => '123456:ABC-DEF',
    'telegram_chat_id' => '-1001234567890',
]);
echo "    Generated On-Login Script Preview:\n";
$lines = explode("\n", $onLoginScript);
foreach (array_slice($lines, 0, 4) as $line) {
    echo "      > {$line}\n";
}
echo "      > ... (Total " . count($lines) . " lines of production RouterOS script)\n\n";

// -------------------------------------------------------------------------
// 5. Mikhmon Official PPPoE Isolir Script & Scheduler
// -------------------------------------------------------------------------
echo "[5] Mikhmon Official PPPoE Isolir Profile & Scheduler Generation:\n";
$isolirScript = $ros->pppoe()->generatePppoeIsolirScript('ISOLIR');
echo "    Generated PPPoE Auto-Isolir Script Preview:\n";
$isoLines = explode("\n", $isolirScript);
foreach (array_slice($isoLines, 0, 5) as $line) {
    echo "      > {$line}\n";
}
echo "      > ... (Total " . count($isoLines) . " lines of RouterOS script)\n\n";

// -------------------------------------------------------------------------
// 6. PPPoE Customer Due Date & Reconnection Flow
// -------------------------------------------------------------------------
echo "[6] PPPoE Customer Billing & Reconnection Flow (Mikhmon Standard):\n";
echo "    - Step 1: Set Due Date on Customer Secret: due:2026-11-20\n";
echo "    - Step 2: Auto-Isolir checks due date. If overdue, switches profile to 'ISOLIR' & kicks active session\n";
echo "    - Step 3: Customer pays bill -> reconnectCustomer('customer_01', '10MBPS', '2026-12-20')\n";
echo "    - Step 4: Router reconnects user immediately on full speed and updates MySQL record.\n\n";

echo "====================================================================\n";
echo " Mikhmon Official Profiles & Multi-Router Demo Successfully Finished!\n";
echo "====================================================================\n";
