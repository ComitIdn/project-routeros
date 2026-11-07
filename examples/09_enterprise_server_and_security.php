<?php
/**
 * 09 - Enterprise Server & Security Architecture
 * SFP/SFP+ 10G/40G Fiber, LACP Bonding (802.3ad), VRRP High-Availability, 802.1Q VLANs,
 * FastTrack CPU Acceleration, and 3-Stage Brute-Force Quarantine.
 */

require __DIR__ . '/../vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;

$ros = RouterOS::withConfig(__DIR__ . '/../config.php');

echo "🛡️ ========================================================\n";
echo "🚀 09 - ENTERPRISE SERVER INTERFACES & SECURITY HARDENING\n";
echo "🛡️ ========================================================\n\n";

$iface = $ros->interface();
$firewall = $ros->firewall();

// 1. Enterprise Physical & Logical Server Interfaces
echo "--- 1. Enterprise Server Interfaces ---\n";
echo "🔹 [SFP / SFP+ Fiber]     \$ros->interface()->getSfp();\n";
echo "🔹 [LACP Bonding 802.3ad] \$ros->interface()->getBonding();\n";
echo "🔹 [VRRP HA Failover]     \$ros->interface()->getVrrp();\n";
echo "🔹 [802.1Q VLAN Trunks]   \$ros->interface()->getVlans();\n";
echo "🔹 [Tunneling Overlays]   \$ros->interface()->getVxlan(); // WireGuard, EoIP, GRE, VXLAN\n";
echo "🔹 [Realtime Traffic]     \$ros->interface()->monitorTraffic('sfp-sfpplus1');\n";

if ($ros->isConnected()) {
    $allInterfaces = $iface->getAll();
    $sfpPorts = $iface->getSfp();
    $bonding = $iface->getBonding();
    $vrrp = $iface->getVrrp();

    echo "📊 Total Interfaces : " . count($allInterfaces) . "\n";
    echo "⚡ SFP Fiber Ports  : " . count($sfpPorts) . "\n";
    echo "🔗 Bonding Trunks   : " . count($bonding) . "\n";
    echo "🔄 VRRP HA Clusters : " . count($vrrp) . "\n";
}

// 2. High-Performance Firewall Security Hardening
echo "\n--- 2. High-Performance Security & Defense ---\n";
echo "🔹 [FastTrack Offloading] \$ros->firewall()->enableFastTrack();\n";
echo "🔹 [Drop Invalid Packets] \$ros->firewall()->dropInvalid();\n";
echo "🔹 [Brute-Force Guard]    \$ros->firewall()->protectBruteForce([22, 8291, 8728, 8729]);\n";
echo "🔹 [Port Scanner Drop]    \$ros->firewall()->protectPortScanners();\n";
echo "🔹 [1-Click Baseline]     \$ros->firewall()->hardenServerBaseline('bridge', 'ether1');\n";

if ($ros->isConnected()) {
    // Apply server baseline security with 1 method call
    $hardenReport = $firewall->hardenServerBaseline('bridge', 'ether1');
    echo "\n✅ Server baseline security successfully applied!\n";
    echo "🛡️ Modules Active: " . implode(', ', $hardenReport['modules']) . "\n";
}

echo "\n💡 Seluruh aturan di atas menjaga server MikroTik (CCR, CRS, CHR, x86) tetap aman,\n";
echo "   stabil pada throughput Gigabit/10G, dan kebal dari serangan brute-force & port scanner.\n";
