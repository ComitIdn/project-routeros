<?php
/**
 * 04 - PPPoE Server & ISP Billing Automation
 * Client secrets, profiles, static IP, ONT/Modem MAC binding, late payment due date, auto-isolate cron.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

$ros = RouterOS::withConfig(__DIR__ . '/../config.php');
$pppoe = $ros->pppoe();

// 1. Add PPPoE Client Secret
$pppoe->addUser('client01', 'secretPass123', 'pppoe', 'default', '', '', 'Rumah Budi Blok A');

// 2. Assign Dedicated Static IP
$pppoe->assignStaticIp('client01', '10.10.10.50', '10.10.10.1');

// 3. Lock Secret to Modem/ONT MAC Address (Prevents account sharing/cloning)
$pppoe->bindCallerId('client01', '00:E0:4C:53:44:58');

// 4. Record Billing Due Date (Database & MikroTik Comment Tag)
$dueDate = date('Y-m-d', strtotime('+30 days'));
$pppoe->setPppoeDueDate('client01', $dueDate, 'INV-2026-001');

// 5. Ensure ISOLIR Profile exists in RouterOS
$pppoe->ensureIsolateProfile('ISOLIR', '10.0.99.1', '10.0.99.2-10.0.99.254', '128k/128k');

// 6. Overdue Detection & Auto-Isolation Cron Job
// Check which customers have passed their billing due date
$overdueList = $pppoe->getOverdueCustomers();
echo "⚠️  Found " . count($overdueList) . " overdue customers.\n";

// Execute automatic mass-isolation (Switches profile to ISOLIR & drops active session)
$isolateResult = $pppoe->autoIsolateOverdue('ISOLIR');
echo "⛔ Isolated {$isolateResult['total_isolated']} customers.\n";

// 7. Unsuspend / Reactivate customer upon invoice payment
$pppoe->unsuspendCustomer('client01', 'default');
echo "✅ Customer client01 reactivated to default profile.\n";
