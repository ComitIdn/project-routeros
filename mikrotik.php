<?php
require 'vendor/autoload.php';

use App\RouterOSConnection;

$config = require 'config.php'; // Load config dari file lain
$routerOS = new RouterOSConnection(
    $config['mikrotik']['host'],
    $config['mikrotik']['user'],
    $config['mikrotik']['password'],
    $config['mikrotik']['port']
);

// 1 Fungsi PPPoE User Management:

// Inisialisasi koneksi
$routerOS = new RouterOSConnection('host', 'username', 'password');

// Mendapatkan semua pengguna PPPoE
$pppoeUsers = $routerOS->getPppoeUsers();

// Mendapatkan detail pengguna PPPoE tertentu
$pppoeUserDetail = $routerOS->getPppoeUserDetails('username1');

// Menambahkan pengguna PPPoE baru
$newPppoeUser = $routerOS->addPppoeUser('username1', 'password123', 'pppoe', 'default');

// Memperbarui pengguna PPPoE (mengganti profil atau password)
$updatePppoeUser = $routerOS->setPppoeUser('username1', 'newPassword', 'newProfile', 'Updated comment');

// Menghapus pengguna PPPoE
$removePppoeUser = $routerOS->removePppoeUser('username1');

// Mendapatkan semua pengguna PPPoE yang aktif
$activePppoeUsers = $routerOS->getActivePppoeUsers();

// Menghapus pengguna PPPoE yang aktif
$removeActivePppoeUser = $routerOS->removeActivePppoeUser('.id_of_active_session');

// 2 Fungsi PPPoE Server Management:

// Mendapatkan semua server PPPoE
$pppoeServers = $routerOS->getPppoeServers();

// Menambahkan server PPPoE baru
$newPppoeServer = $routerOS->addPppoeServer('server1', 'ether1', 'pppoeService');

// Menghapus server PPPoE
$removePppoeServer = $routerOS->removePppoeServer('server1');

// 3. Fungsi PPPoE Profile Management:

// Mendapatkan semua profil PPPoE
$pppoeProfiles = $routerOS->getPppoeProfiles();

// Menambahkan profil PPPoE baru
$newPppoeProfile = $routerOS->addPppoeProfile('profile1', '192.168.88.1', '192.168.88.100', '5M/5M');

// Menghapus profil PPPoE
$removePppoeProfile = $routerOS->removePppoeProfile('profile1');

// 4. Fungsi Hotspot Management:

// Mendapatkan semua pengguna Hotspot
$hotspotUsers = $routerOS->getHotspotUsers();

// Menambahkan pengguna Hotspot baru
$newHotspotUser = $routerOS->addHotspotUser('username1', 'password123', 'default', '1h', 'Test user');

// Memperbarui pengguna Hotspot
$updateHotspotUser = $routerOS->setHotspotUser('username1', 'newPassword', '2h', 'Updated comment', 'default');

// Menghapus pengguna Hotspot
$removeHotspotUser = $routerOS->removeHotspotUser('username1');

// Mendapatkan pengguna Hotspot yang aktif
$activeHotspotUsers = $routerOS->getActiveHotspotUsers();

// Menghapus pengguna Hotspot yang aktif
$removeActiveHotspotUser = $routerOS->removeActiveHotspotUser('.id_of_active_session');

// Mendapatkan pengguna Hotspot yang sudah expired
$expiredHotspotUsers = $routerOS->getExpiredHotspotUsers();

// 5. Fungsi Hotspot Server Management:

// Mendapatkan semua server Hotspot
$hotspotServers = $routerOS->getHotspotServers();

// Menambahkan server Hotspot baru
$newHotspotServer = $routerOS->addHotspotServer('server1', 'ether1', 'default');

// Menghapus server Hotspot
$removeHotspotServer = $routerOS->removeHotspotServer('server1');

// 6. Fungsi Hotspot Server Profile Management:

// Mendapatkan semua profil server Hotspot
$hotspotServerProfiles = $routerOS->getHotspotServerProfiles();

// Menambahkan profil server Hotspot baru
$newHotspotServerProfile = $routerOS->addHotspotServerProfile('profile1', 'dns.example.com', 'cookie,mac');

// Menghapus profil server Hotspot
$removeHotspotServerProfile = $routerOS->removeHotspotServerProfile('profile1');

// 7. Fungsi IP Binding Hotspot:

// Mendapatkan semua IP binding Hotspot
$hotspotIpBindings = $routerOS->getHotspotIpBindings();

// Menambahkan IP binding baru untuk pengguna Hotspot
$newIpBinding = $routerOS->addHotspotIpBinding('00:11:22:33:44:55', 'bypassed', '192.168.88.10');

// Menghapus IP binding
$removeIpBinding = $routerOS->removeHotspotIpBinding('.id_of_ip_binding');

// 8. Fungsi Firewall Management:

// Mendapatkan semua aturan firewall filter
$firewallFilters = $routerOS->getFirewallFilterRules();

// Mendapatkan semua aturan firewall NAT
$firewallNatRules = $routerOS->getFirewallNatRules();

// Mendapatkan semua aturan firewall Mangle
$firewallMangleRules = $routerOS->getFirewallMangleRules();

// Mendapatkan semua aturan firewall RAW
$firewallRawRules = $routerOS->getFirewallRawRules();

// 9. Fungsi DNS Management:

// Mendapatkan semua entri cache DNS
$dnsCache = $routerOS->getDnsCache();

// Mendapatkan pengaturan DNS
$dnsSettings = $routerOS->getDnsSettings();

// 10. Fungsi Sistem Lain:

// Mendapatkan resource sistem (CPU, memori, uptime, dll)
$systemResources = $routerOS->getSystemResources();

// Reboot router
$rebootRouter = $routerOS->rebootRouter();

// 11. Fungsi Queue Management:

// Mendapatkan semua antrean (queues)
$queues = $routerOS->getQueues();

// Menambahkan antrean baru
$newQueue = $routerOS->addQueue('queue1', '192.168.88.0/24', '10M/10M');

// 12. Fungsi Tools (Ping, Netwatch):

// Melakukan ping ke alamat IP tertentu
$pingResult = $routerOS->ping('8.8.8.8');

// Mendapatkan semua entri Netwatch
$netwatchEntries = $routerOS->getNetwatch();

// 13. Fungsi Logs:

// Mendapatkan log sistem
$logs = $routerOS->getLogs();

// 14. Fungsi Custom Command:

// Menjalankan perintah kustom di MikroTik
$customCommand = $routerOS->customCommand('/interface/print');
