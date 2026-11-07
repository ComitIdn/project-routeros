<div align="center" id="top">
  <h1>⚡ Project RouterOS PHP Framework (Full Version)</h1>
  <p><b>Lightweight, Modular &amp; Enterprise-Ready PHP API Framework for MikroTik RouterOS</b><br>
  Inspired by <a href="https://github.com/comitIdn/RouterOS-API">comitIdn/RouterOS-API</a> &bull; Full Support for <b>Manual API</b>, <b>SQL Database (SQLite &amp; MySQL)</b>, <b>Flat-File JSON Storage</b>, <b>Web Apps</b>, <b>Chat Bots (Telegram/WhatsApp)</b>, <b>Payment Gateways</b>, and <b>Terminal CLI</b></p>
</div>

<p align="center">
  <img alt="PHP Version" src="https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?logo=php&logoColor=white">
  <img alt="RouterOS" src="https://img.shields.io/badge/RouterOS-v6%20%7C%20v7-E05A47?logo=mikrotik&logoColor=white">
  <img alt="Database" src="https://img.shields.io/badge/Database-SQLite%20%7C%20MySQL%20%7C%20JSON-003B57?logo=sqlite&logoColor=white">
  <img alt="JSON API" src="https://img.shields.io/badge/API-REST%20JSON%20CORS-blue">
  <img alt="Bots" src="https://img.shields.io/badge/Bots-Telegram%20%7C%20WhatsApp-25D366?logo=whatsapp&logoColor=white">
  <img alt="Payment" src="https://img.shields.io/badge/Billing-Tripay%20%7C%20Midtrans%20%7C%20QRIS-orange">
  <img alt="License" src="https://img.shields.io/badge/License-MIT-green">
</p>

<p align="center">
  <a href="#-fitur-utama-framework">Fitur Utama</a> &nbsp;|&nbsp;
  <a href="#-struktur-arsitektur">Arsitektur</a> &nbsp;|&nbsp;
  <a href="#-instalasi--konfigurasi">Instalasi</a> &nbsp;|&nbsp;
  <a href="#-gaya-penggunaan-lengkap">Gaya Penggunaan</a> &nbsp;|&nbsp;
  <a href="#-modul-tambahan-powerhouse">Modul Tambahan</a> &nbsp;|&nbsp;
  <a href="#-integrasi-bot-telegram--whatsapp">Bot Integration</a> &nbsp;|&nbsp;
  <a href="#-payment-gateway--auto-voucher">Payment Gateway</a> &nbsp;|&nbsp;
  <a href="#-rest-api-json-endpoint">REST API</a> &nbsp;|&nbsp;
  <a href="#-terminal-cli-console-tool">Terminal CLI</a> &nbsp;|&nbsp;
  <a href="#-cheat-sheet-modul-lengkap">Cheat Sheet</a>
</p>

---

## 🌟 Fitur Utama Framework

1. **Gaya Klasik `comitIdn/RouterOS-API` Kompatibel Penuh**:
   - Mendukung metode socket native: `connect()`, `comm()`, `write()`, `read()`, dan `disconnect()`.
   - Mengintegrasikan fungsi utilitas bawaan: `formatBytes()`, `convertToFullMonthName()`, `formatInterval()`, `encrypt()`, dan `decrypt()`.
2. **Dua Jenis Penyimpanan Database (SQL &amp; JSON)**:
   - **SQL Database (PDO)**: Bawaan **SQLite** tanpa instalasi server database (*zero-config*) dan dukungan **MySQL / MariaDB / PostgreSQL**.
   - **JSON Flat-File Storage**: Simpan dan ambil data konfigurasi, voucher, dan log dalam bentuk file JSON langsung (`database/json/`).
3. **Modul Lengkap (*Full Version*)**:
   - **Hotspot & Voucher Engine**: Generator voucher massal, batas kuota/waktu, cetak kartu voucher siap print, kick user, dan auto-clean expired.
   - **Device Bypass & Host**: Bypass Smart TV, CCTV, printer tanpa login portal, dan blokir MAC mencurigakan.
   - **PPPoE & Queue**: Manajemen secret pelanggan ISP, profil bandwidth, dan pembatas kecepatan Simple Queue.
   - **Firewall & IP**: Filter, NAT Masquerade, Mangle, RAW, Address List, IP Pool, DHCP Server, DHCP Lease, ARP, dan Static DNS.
   - **Interface & System**: Monitoring trafik *real-time* RX/TX, Ethernet, Bridge, WireGuard, Clock, Script, Scheduler, Log, Ping, dan Reboot.
   - **Backup & Export**: Pembuatan binary backup `.backup` dan export skrip `.rsc` otomatis.
   - **Network Tools**: Wake-on-LAN (WOL remote boot PC), Email alert, SMS sender, Traceroute, IP Scan, dan Bandwidth Speed Test.
   - **Wireless (Wi-Fi)**: Registration table, sinyal dBm, ganti password Wi-Fi, ganti SSID, dan kick client Wi-Fi.
   - **VPN & Tunneling**: WireGuard peers & interface, L2TP, OpenVPN, dan SSTP server.
   - **Billing & Payment Gateway**: Webhook otomatis Tripay, Midtrans, Duitku, Xendit, dan QRIS untuk menerbitkan voucher otomatis saat pembayaran sukses.
   - **AdBlock & DNS Filter**: Pemblokir iklan / malware berbasis DNS redirect.
4. **Siap Pakai untuk Segala Macam Project**:
   - **Website Skala Besar (Enterprise)**: Dilengkapi *Multi-Router Manager* dan *Caching Layer* (TTL) agar CPU MikroTik tidak *overload*.
   - **REST API JSON**: Endpoint [`api.php`](file:///data/data/com.termux/files/home/project-routeros/api.php) siap konsumsi untuk frontend modern (React, Vue, Flutter).
   - **Chat Bot (Telegram & WhatsApp)**: Endpoint [`bot_webhook.php`](file:///data/data/com.termux/files/home/project-routeros/bot_webhook.php) dan class [`BotHandler`](file:///data/data/com.termux/files/home/project-routeros/src/Modules/BotHandler.php).
   - **Terminal CLI Console**: Skrip interaktif [`console.php`](file:///data/data/com.termux/files/home/project-routeros/console.php) untuk eksekusi langsung dari Terminal / Termux.

---

## 🏗️ Struktur Arsitektur

```text
project-routeros/
├── config.example.php          # Template Konfigurasi Router & Database
├── config.php                  # Konfigurasi Lokal (Host, User, Pass, DB)
├── composer.json               # Dependensi & Autoloading PSR-4 + Laravel Auto-Discovery
├── example.php                 # Master Contoh Implementasi Bersih & Profesional
├── mikrotik.php                # Alias Kompatibilitas ke Belakang (Forwarder ke example.php)
├── console.php                 # CLI Console Tool Interaktif untuk Terminal / Termux
├── api.php                     # Universal RESTful JSON API Endpoint (CORS Ready)
├── bot_webhook.php             # Webhook Handler Telegram & WhatsApp Bot
├── index.php                   # Web Dashboard & Generator Voucher Interaktif
├── examples/                   # Direktori Contoh Kode Modular & Terstruktur
│   ├── 01_quickstart.php       # Inisialisasi koneksi, resource, ping & version
│   ├── 02_hotspot.php          # Hotspot user, limits, traffic realtime, bypass
│   ├── 03_voucher.php          # Bulk voucher, QR Code, print card, thermal printer
│   ├── 04_pppoe.php            # PPPoE secrets, static IP, billing overdue & auto-isolate
│   ├── 05_actions.php          # Dedicated Actions: Finder, Setter, Getter, Printer
│   └── 06_multi_platform.php   # Panduan integrasi Laravel, CodeIgniter, JS SDK, CLI
├── assets/
│   └── js/
│       └── routeros.js         # Client-side JavaScript SDK Universal (Vanilla, Vue, React, Node)
├── database/
│   ├── schema.sql              # Skema SQL (routers, vouchers, users, logs)
│   ├── routeros.sqlite         # Database SQLite lokal (dibuat otomatis)
│   ├── json/                   # Direktori penyimpanan database flat JSON
│   └── cache/                  # Direktori cache response MikroTik (TTL)
├── src/
│   ├── Actions/                # File Operasional Terpisah & Bersih (Superpower CRUD)
│   │   ├── Finder.php          # Pencarian .id dari name (findNameToId), byName, byId, byMac, byIp
│   │   ├── Setter.php          # Pengaturan nilai granular: setLimit, setComment, setEmail, setName
│   │   ├── Getter.php          # Pengambilan data fleksibel: getByName, getValue, count, exists
│   │   ├── Printer.php         # Eksekusi /print dengan filter, proplist spesifik & paginasi
│   │   ├── Remover.php         # Penghapusan aman berdasarkan name, ID, atau kriteria massal
│   │   └── Toggler.php         # Enable, disable, toggle status, dan reset-counters
│   ├── Integrations/           # Bridge Multi-Framework
│   │   ├── Laravel/
│   │   │   ├── RouterOSServiceProvider.php # Auto-Discovery ServiceProvider Laravel
│   │   │   └── Facades/RouterOS.php        # Facade RouterOS::hotspot()
│   │   └── CodeIgniter/
│   │       └── RouterOSLibrary.php         # Library Drop-in CodeIgniter 3 & 4
│   ├── Core/
│   │   ├── RouterOSAPI.php     # Client gaya comitIdn/RouterOS-API (connect, comm, write)
│   │   ├── RouterOS.php        # Unified Framework Facade & Module Accessor
│   │   ├── Connection.php      # Socket connection dengan error recovery
│   │   ├── Database.php        # Lightweight SQL PDO Engine (SQLite & MySQL)
│   │   ├── JsonStorage.php     # Engine Database File JSON Flat-Store
│   │   ├── Cache.php           # High-Performance TTL Caching Layer
│   │   ├── MultiRouter.php     # Enterprise Multi-Router Manager
│   │   ├── Response.php        # Standardized JSON Response Formatter
│   │   └── Helper.php          # Utilitas format bytes, validasi IP/MAC, PIN acak
│   └── Modules/
│       ├── Hotspot.php         # Manajemen Hotspot lengkap (User, Active, Host, Bypass)
│       ├── Voucher.php         # Generator Voucher, Simpan SQL/JSON, Cetak Kupon
│       ├── PPPoE.php           # Manajemen PPPoE (Secret, Profile, Active, Server, Isolir)
│       ├── Firewall.php        # Filter, NAT Masquerade, Mangle, RAW, Address List
│       ├── Queue.php           # Simple Queue & Bandwidth Limiter otomatis
│       ├── NetworkInterface.php# Monitoring Interface, Traffic Real-time, Bridge, WireGuard
│       ├── System.php          # Resources, Clock, Reboot, Ping, Script, Scheduler, v6/v7 Check
│       ├── Ip.php              # Addresses, Pools, DHCP Server, DHCP Lease, ARP, Static DNS
│       ├── Backup.php          # Binary Backup (.backup) & Export Konfigurasi (.rsc)
│       ├── Tool.php            # Wake-on-LAN, Email, SMS, Traceroute, IP Scan, Bandwidth Test
│       ├── Wireless.php        # WiFi Client Table, Sinyal dBm, Ganti SSID & Password WiFi
│       ├── Vpn.php             # WireGuard Peers & Interface, L2TP, OpenVPN, SSTP
│       ├── Billing.php         # Payment Gateway Webhook (Tripay, Midtrans, QRIS)
│       ├── DnsFilter.php       # DNS AdBlocker & Domain Redirect Blacklist
│       └── BotHandler.php      # Parser Perintah Bot Telegram & WhatsApp Gateway
└── RouterOSConnection.php      # Wrapper kompatibel ke belakang (Backward-compatible)
```

---

## 🚀 Instalasi & Konfigurasi

### 1. Kebutuhan Sistem
- PHP 7.4 atau PHP 8.x
- Ekstensi PHP: `pdo`, `pdo_sqlite` (atau `pdo_mysql`), `sockets`, `curl`, `json`
- MikroTik RouterOS dengan API aktif (`/ip service enable api`)

### 2. Instalasi
```bash
git clone https://github.com/comitIdn/project-routeros.git
cd project-routeros
composer install
```

### 3. Konfigurasi (`config.php`)
```php
return [
    'mikrotik' => [
        'host'     => '192.168.88.1',
        'user'     => 'admin',
        'password' => 'password123',
        'port'     => 8728, // Default RouterOS API port
        'timeout'  => 3,
    ],

    // Database SQL (Default: SQLite zero-config, atau MySQL)
    'database' => [
        'driver'   => 'sqlite', // 'sqlite' atau 'mysql'
        'file'     => __DIR__ . '/database/routeros.sqlite',

        // Jika menggunakan MySQL:
        // 'host'     => '127.0.0.1',
        // 'port'     => 3306,
        // 'database' => 'routeros',
        // 'username' => 'root',
        // 'password' => '',
    ],
];
```

---

## 💡 Gaya Penggunaan Lengkap

### Gaya 1: comitIdn/RouterOS-API Style (Sangat Familiar & Cepat)
Menggunakan sintaks khas `comitIdn/RouterOS-API` (`connect`, `comm`, `write`, `read`):

```php
require 'vendor/autoload.php';

use RouterOSFramework\Core\RouterOSAPI;

$api = new RouterOSAPI();
$api->connect('192.168.88.1', 'admin', 'password', 8728);

// 1. Eksekusi perintah instan dengan comm()
$interfaces = $api->comm('/interface/print');
$hotspotUsers = $api->comm('/ip/hotspot/user/print', ['?profile' => 'default']);

// 2. Eksekusi bertingkat dengan write() dan read()
$api->write('/ip/hotspot/active/print');
$activeUsers = $api->read();

// 3. Akses modul Hotspot, Voucher, Backup, atau Tools dari objek yang sama!
$summary = $api->hotspot()->getSummary();
$vouchers = $api->voucher()->generateAndSave(5, ['prefix' => 'VC-', 'limit-uptime' => '2h']);
$api->tool()->wakeOnLan('00:11:22:33:44:55', 'ether1');

$api->disconnect();
```

---

### Gaya 2: Modern OOP Modular Framework Style
```php
use RouterOSFramework\Core\RouterOS;

// Inisialisasi otomatis dari config.php
$ros = RouterOS::withConfig('config.php');

// Hotspot
$activeSessions = $ros->hotspot()->getActiveTraffic(); // Bandwidth human-readable
$ros->hotspot()->disconnectUser('budi');               // Kick user
$ros->hotspot()->bypassDevice('00:11:22:33:44:55');    // Bypass Smart TV

// Simple Queue
$ros->queue()->limitBandwidth('192.168.88.50/32', '10M/10M', 'Limit-Staff');

// Firewall
$ros->firewall()->addMasquerade('ether1');
```

---

### Gaya 3: Engine Database SQL (SQLite & MySQL)
```php
// Buat voucher dan otomatis simpan ke RouterOS + Database SQL
$batch = $ros->voucher()->generateAndSave(10, [
    'prefix'            => 'VC-',
    'length'            => 6,
    'characters'        => 'alphanumeric',
    'profile'           => 'default',
    'limit-uptime'      => '2h',
    'limit-bytes-total' => '1073741824', // 1 GB
    'price'             => 5000,
    'batch_tag'         => 'batch-kafe-01',
]);

// Query voucher langsung dari database SQL lokal
$vouchers = $ros->voucher()->getFromDb(['status' => 'active', 'limit' => 20]);

// Cetak kartu voucher siap cetak (Print-Ready HTML)
echo $ros->voucher()->renderPrintCards($vouchers, [
    'title'    => 'WIFI CAFE HOTSPOT',
    'dns_name' => 'http://wifi.cafe',
    'price'    => 'Rp 5.000 / 2 Jam',
]);
```

---

### Gaya 4: Engine Flat-File JSON Storage
Simpan dan baca data dalam format file JSON langsung tanpa perlu menginstal database server:

```php
$json = $ros->json();

// 1. Simpan konfigurasi ke database/json/app_config.json
$json->set('app_config', 'site_name', 'Hotspot RT/RW Net');

// 2. Tambah data transaksi ke database/json/transactions.json
$json->push('transactions', [
    'voucher_code' => 'VC-8k2j9a',
    'customer'     => 'Budi Santoso',
    'amount'       => 5000,
    'created_at'   => date('Y-m-d H:i:s'),
]);

// 3. Cari data dari file JSON
$customerData = $json->find('transactions', 'customer', 'Budi Santoso');
```

---

## ⚡ Fitur Lanjutan Hotspot, Voucher Massal & PPPoE ISP Billing

Tiga modul inti (`$ros->hotspot()`, `$ros->voucher()`, `$ros->pppoe()`) telah diperkaya dengan serangkaian metode praktis kelas produksi:

### 1. Advanced Hotspot (`$ros->hotspot()`)
```php
// 1. Pantau traffic RX/TX dan bytes per user secara real-time
$traffic = $ros->hotspot()->getUserTraffic('budi');
// Hasil: ['uptime' => '2h15m', 'rx_human' => '12.4 MB', 'tx_human' => '105.8 MB', 'total_human' => '118.2 MB']

// 2. Dapatkan 10 pengguna dengan konsumsi kuota terbesar (Top Downloaders)
$topDownloaders = $ros->hotspot()->getTopDownloaders(10);

// 3. Dapatkan daftar pengguna dengan waktu aktif terlama (Top Active Session)
$topActive = $ros->hotspot()->getTopActiveTime(5);

// 4. Perpanjang masa aktif / limit uptime user (+2 jam)
$ros->hotspot()->extendUserTime('budi', '2h');

// 5. Tambah kuota data user (+1 GB)
$ros->hotspot()->extendUserData('budi', '1G'); // atau integer byte 1073741824

// 6. Kunci akun Hotspot ke MAC address perangkat tertentu (Anti-Tethering / Anti-Kloning)
$ros->hotspot()->lockUserToMac('budi', '00:1A:2B:3C:4D:5E');

// 7. Buka kembali kunci MAC address
$ros->hotspot()->unlockUserMac('budi');

// 8. Ambil daftar IP lease DHCP khusus klien hotspot
$leases = $ros->hotspot()->getHotspotDhcpLeases();
```

### 2. Advanced Voucher Engine (`$ros->voucher()`)
```php
// 1. Generate URL QR Code otomatis (Scan kamera langsung login tanpa ketik password)
$qrUrl = $ros->voucher()->generateQrCodeUrl('VC-8k2j9a', 'pass123', 'http://wifi.cafe/login', 120);

// 2. Render kartu voucher siap cetak (HTML Print Cards) lengkap dengan QR Code
$kartuHtml = $ros->voucher()->renderPrintCards($vouchers, [
    'title'    => 'HOTSPOT CAFE FREE WIFI',
    'dns_name' => 'http://wifi.cafe/login',
    'price'    => 'Rp 5.000 / 2 Jam',
    'show_qr'  => true, // Otomatis tampilkan QR Code login
]);

// 3. Format Struk Printer Kasir Bluetooth Thermal 58mm / 80mm (ESC/POS Ready)
$strukThermal = $ros->voucher()->renderThermalReceipt($voucher, [
    'store_name' => 'WARKOP CYBER CAFE',
    'address'    => 'Jl. Thamrin No. 45',
    'login_url'  => 'http://wifi.cafe',
]);
// Kirim string $strukThermal langsung ke printer Bluetooth / USB Thermal POS!

// 4. Laporan Penjualan & Keuangan Voucher Realtime
$sales = $ros->voucher()->getSalesReport();
/*
Hasil: [
    'total_vouchers'  => 100,
    'active_vouchers' => 70,
    'used_vouchers'   => 30,
    'total_potential' => 500000,
    'total_realized'  => 150000,
    'realized_human'  => 'Rp 150.000'
]
*/

// 5. Rekap Kinerja Berdasarkan Tag Batch
$batchKafe = $ros->voucher()->getBatchSummary('batch-kafe-01');

// 6. Export Voucher ke File CSV (Bisa langsung dibuka di Microsoft Excel)
$csv = $ros->voucher()->exportToCsv('batch-kafe-01', __DIR__ . '/database/exports/vouchers.csv');

// 7. Import Voucher Massal dari File CSV ke MikroTik & Database SQL
$importRes = $ros->voucher()->importFromCsv(__DIR__ . '/database/exports/vouchers.csv', 'default');
```

### 3. Advanced PPPoE & ISP Billing Management (`$ros->pppoe()`)
```php
// 1. Pantau traffic live RX/TX, IP address, Caller ID ONT/Modem pelanggan
$session = $ros->pppoe()->getUserTraffic('client01');

// 2. Kunci akun PPPoE ke MAC address Modem/ONT pelanggan (Anti-Sharing ISP)
$ros->pppoe()->bindCallerId('client01', '00:E0:4C:53:44:58');
$ros->pppoe()->unbindCallerId('client01');

// 3. Tetapkan IP Statis / Publik untuk pelanggan dedicated
$ros->pppoe()->assignStaticIp('client01', '10.10.10.50', '10.10.10.1');

// 4. Pastikan Profile ISOLIR sudah siap di MikroTik
$ros->pppoe()->ensureIsolateProfile('ISOLIR', '10.0.99.1', '10.0.99.2-10.0.99.254', '128k/128k');

// 5. Catat tanggal jatuh tempo tagihan (Due Date) ke Database SQL & Komentar MikroTik
$ros->pppoe()->setPppoeDueDate('client01', '2026-11-20', 'INV-2026-001');

// 6. Deteksi otomatis semua pelanggan yang terlambat bayar / jatuh tempo
$overdueCustomers = $ros->pppoe()->getOverdueCustomers();

// 7. Isolir Massal Otomatis Pelanggan Nunggak (Ganti Profile ke ISOLIR + Kick Sesi Aktif)
// Sangat ideal dijalankan pada skrip cron harian:
$hasilIsolir = $ros->pppoe()->autoIsolateOverdue('ISOLIR');

// 8. Buka isolir pelanggan saat sudah membayar tagihan (Re-aktivasi)
$ros->pppoe()->unsuspendCustomer('client01', 'default');
```

---

## 🛠️ File Operasional Terpisah: Finder, Setter, Getter, Printer, Remover, Toggler

Seluruh fungsi operasional dipisahkan ke dalam file PHP masing-masing di [`src/Actions/`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/) agar kode tetap rapi, terstruktur, tidak ada fungsi ganda/dobel nama, dan memiliki kapabilitas superpower yang mudah diakali:

| File Action | Lokasi File | Metode Utama |
| :--- | :--- | :--- |
| **`Finder`** | [`src/Actions/Finder.php`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/Finder.php) | `nameToId()`, `findname_toId()`, `byName()`, `byId()`, `byMac()`, `byIp()`, `where()`, `first()`, `exists()` |
| **`Setter`** | [`src/Actions/Setter.php`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/Setter.php) | `limit()` / `setLimit()`, `comment()` / `setComment()`, `email()` / `setEmail()`, `name()` / `setName()`, `password()`, `profile()`, `property()` |
| **`Getter`** | [`src/Actions/Getter.php`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/Getter.php) | `get()`, `getByName()`, `getById()`, `getValue()`, `count()`, `exists()`, `pluck()` |
| **`Printer`** | [`src/Actions/Printer.php`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/Printer.php) | `print()`, `printColumns()`, `printDetail()`, `printJson()`, `paginate()` |
| **`Remover`** | [`src/Actions/Remover.php`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/Remover.php) | `remove()`, `byName()`, `byId()`, `where()`, `byComment()`, `byProfile()`, `all()` |
| **`Toggler`** | [`src/Actions/Toggler.php`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/Toggler.php) | `enable()`, `disable()`, `toggle()`, `resetCounter()` |

#### Contoh Penggunaan Action Terpisah:
```php
// 1. Finder: Cari .id RouterOS dari nama
$userId = $ros->find()->nameToId('budi', '/ip/hotspot/user'); // Hasil: '*1A'
$item = $ros->find()->byMac('00:1A:2B:3C:4D:5E');

// 2. Setter: Modifikasi granular tanpa memuat seluruh objek
$ros->set()->limit('budi', '2h', '1G');
$ros->set()->comment('budi', 'Langganan VIP');
$ros->set()->email('budi', 'budi@gmail.com');
$ros->set()->name('budi', 'budi_baru'); // Rename item aman
$ros->set()->property('/ip/firewall/nat', '*1', 'comment', 'NAT Ether1 Internet');

// 3. Getter: Ambil satu nilai properti tertentu saja (Sangat hemat memori router)
$userUptime = $ros->get()->getValue('/ip/hotspot/user', 'budi_baru', 'uptime');
$userCount  = $ros->get()->count('/ip/hotspot/user', ['profile' => 'default']);

// 4. Printer: Ambil kolom spesifik & Paginasi otomatis
$columnsOnly = $ros->print()->printColumns('/ip/hotspot/user', ['name', 'profile', 'limit-uptime']);
$pagedUsers  = $ros->print()->paginate('/ip/hotspot/user', 1, 15);

// 5. Remover: Hapus berdasarkan kriteria
$ros->remove()->byName('tester', '/ip/hotspot/user');
$ros->remove()->where('/ip/hotspot/user', ['comment' => 'expired']);

// 6. Toggler: Toggle status aktif / non-aktif
$ros->toggle()->toggle('/ip/hotspot/user', 'budi_baru');
```

---

## 🔌 Dukungan Multi-Platform (PHP Manual, Laravel, CodeIgniter, JS SDK, CLI)

Framework ini dirancang universal dan dapat langsung digunakan pada segala jenis arsitektur proyek:

### 1. PHP Native / Manual
```php
require 'vendor/autoload.php';
use RouterOSFramework\Core\RouterOS;

$ros = RouterOS::withConfig('config.php');
$ros->setLimit('budi', '2h');
$ros->setComment('budi', 'VIP RT/RW Net');
```

### 2. Laravel (8.x, 9.x, 10.x, 11.x, dst)
Otomatis terdaftar melalui **Laravel Package Auto-Discovery**:
```php
use RouterOS;

class HotspotController extends Controller
{
    public function updateLimit(Request $request)
    {
        RouterOS::setLimit($request->user, $request->limit);
        RouterOS::setComment($request->user, $request->comment);
        return response()->json(['status' => 'success']);
    }
}
```

### 3. CodeIgniter (CI3 & CI4)
Gunakan library [`RouterOSLibrary.php`](file:///data/data/com.termux/files/home/project-routeros/src/Integrations/CodeIgniter/RouterOSLibrary.php):
```php
// CodeIgniter 3:
$this->load->library('RouterOSLibrary');
$this->routeroslibrary->setComment('budi', 'VIP');

// CodeIgniter 4:
$ros = new \RouterOSFramework\Integrations\CodeIgniter\RouterOSLibrary();
$users = $ros->hotspot()->getUsers();
```

### 4. Client-side JavaScript SDK ([`assets/js/routeros.js`](file:///data/data/com.termux/files/home/project-routeros/assets/js/routeros.js))
Frontend web (Vue, React, Svelte, atau HTML polos) dapat langsung berinteraksi dengan RouterOS melalui Promises / `async / await`:
```html
<script src="assets/js/routeros.js"></script>
<script>
const client = new RouterOSClient({ endpoint: '/api.php' });

async function init() {
    // Ambil data users
    const users = await client.hotspot.getUsers();
    
    // Ubah limit, komentar, atau isolir langsung dari JavaScript:
    await client.hotspot.setLimit('budi', '2h');
    await client.hotspot.setComment('budi', 'VIP Client');
    await client.pppoe.suspend('client01');
}
init();
</script>
```

### 5. Terminal CLI Console ([`console.php`](file:///data/data/com.termux/files/home/project-routeros/console.php))
```bash
php console.php setlimit budi 2h 1G
php console.php setcomment budi "Langganan VIP"
php console.php setemail budi budi@gmail.com
php console.php setname budi budi_baru
php console.php findid budi
php console.php suspend client01
php console.php unsuspend client01
```

### 6. Deteksi & Dukungan RouterOS v6.xx - v7.xx
```php
$isV7 = $ros->system()->isV7(); // true jika RouterOS v7.xx
$isV6 = $ros->system()->isV6(); // true jika RouterOS v6.xx
$ver  = $ros->system()->getVersion(); // e.g. "7.15.2"
```

---

## 🔥 Modul Tambahan Powerhouse

### 1. Modul Backup & Export (`$ros->backup()`)
```php
// Buat binary backup di storage MikroTik
$ros->backup()->saveBackup('backup-mingguan', 'passwordEnkripsi123');

// Export konfigurasi script .rsc yang rapi (compact & hide-sensitive)
$ros->backup()->exportConfig('config-router-hq');

// Lihat daftar file di storage router
$files = $ros->backup()->getFiles();
```

### 2. Modul Network Tools (`$ros->tool()`)
```php
// Wake-on-LAN: Nyalakan PC/Server kantor dari jarak jauh
$ros->tool()->wakeOnLan('AA:BB:CC:DD:EE:FF', 'ether2');

// Kirim Email Notifikasi langsung dari MikroTik
$ros->tool()->sendEmail('admin@perusahaan.com', 'Router Alert', 'Suhu router normal.');

// Kirim SMS via Modem USB/LTE attached
$ros->tool()->sendSms('08123456789', 'Token listrik kantor hampir habis!');

// Traceroute & IP Scan
$hops = $ros->tool()->traceroute('1.1.1.1');
$activeIps = $ros->tool()->ipScan('ether1', '5s');
```

### 3. Modul Wireless Wi-Fi (`$ros->wireless()`)
```php
// Lihat perangkat yang sedang terhubung ke Wi-Fi lengkap dengan sinyal dBm
$wifiClients = $ros->wireless()->getConnectedClients();

// Ganti password Wi-Fi WPA2
$ros->wireless()->setWifiPassword('default', 'PasswordBaru2026!');

// Ganti nama SSID Wi-Fi
$ros->wireless()->setSsid('wlan1', 'FREE-WIFI-CAFE');

// Kick perangkat yang terhubung ke Wi-Fi berdasarkan MAC
$ros->wireless()->disconnectClient('00:11:22:33:44:55');
```

### 4. Modul VPN & Tunneling (`$ros->vpn()`)
```php
// Lihat peer WireGuard
$peers = $ros->vpn()->getWireguardPeers();

// Tambah Peer WireGuard baru
$ros->vpn()->addWireguardPeer('wireguard1', 'PublicKeyBase64...', '10.0.0.2/32', 'vpn.kantor.com', 51820);

// Status VPN Server (L2TP, OpenVPN, SSTP)
$l2tp = $ros->vpn()->getL2tpServer();
$ovpn = $ros->vpn()->getOvpnServer();
```

### 5. Modul DNS Filter & AdBlocker (`$ros->dnsFilter()`)
```php
// Blokir domain iklan atau situs berbahaya via DNS redirect
$ros->dnsFilter()->blockDomain('ads.google.com', '0.0.0.0', 'Blokir Iklan');

// Import daftar ribuan domain blocklist (Hosts file)
$ros->dnsFilter()->importHostsBlocklist(['doubleclick.net', 'adservice.google.com']);

// Flush DNS Cache
$ros->dnsFilter()->flushCache();
```

---

## 💳 Payment Gateway & Auto Voucher (`$ros->billing()`)

Integrasi pembayaran otomatis untuk Tripay, Midtrans, Duitku, Xendit, dan QRIS:

```php
$billing = $ros->billing();

// Terima webhook POST dari payment gateway
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

// Proses webhook & otomatis generate voucher jika status PAID
$result = $billing->handleWebhook('tripay', $payload, [
    'PAKET-2JAM' => ['profile' => 'default', 'limit-uptime' => '2h', 'price' => 5000],
    'PAKET-1HARI' => ['profile' => 'default', 'limit-uptime' => '24h', 'price' => 15000],
]);

if ($result['success']) {
    // Teks WhatsApp siap kirim ke nomor pembeli:
    echo $result['wa_message'];
}
```

---

## 🤖 Integrasi Bot Telegram & WhatsApp

Modul [`BotHandler`](file:///data/data/com.termux/files/home/project-routeros/src/Modules/BotHandler.php) dan webhook [`bot_webhook.php`](file:///data/data/com.termux/files/home/project-routeros/bot_webhook.php):

```php
$bot = $ros->bot();

// Terima pesan perintah apa pun dari chat:
$reply = $bot->handle('/status');
echo $reply['text']; // Balasan berformat rapi dengan emoji

// Perintah yang didukung:
// - /help                        -> Menu bantuan perintah
// - /status                      -> Laporan resource CPU, RAM, & user
// - /traffic                     -> Pantau trafik bandwidth live
// - /vc [jumlah] [profil] [jam]  -> Buat voucher Hotspot instan
// - /kick [username]             -> Putuskan user online
// - /bypass [mac] [nama]         -> Bypass perangkat tanpa login
```

---

## 💻 Terminal CLI Console Tool

Jalankan perintah langsung dari Terminal atau Termux:

```bash
# Cek status resource & user router
php console.php status

# Buat 10 voucher Hotspot paket 2 jam
php console.php voucher 10 default 2h

# Putuskan sesi user aktif
php console.php kick budi

# Bypass perangkat MAC tanpa login
php console.php bypass 00:11:22:33:44:55 "Smart TV"

# Buat binary backup di storage router
php console.php backup backup-bulanan

# Nyalakan PC kantor via Wake-on-LAN
php console.php wol AA:BB:CC:DD:EE:FF ether1

# Eksekusi custom RouterOS command
php console.php comm "/ip/address/print"
```

---

## 🌐 REST API JSON Endpoint

File [`api.php`](file:///data/data/com.termux/files/home/project-routeros/api.php) siap dihubungkan ke frontend modern:

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api.php?action=status` | Status resource, identitas router, dan metrik hotspot |
| `GET` | `/api.php?action=users` | Daftar pengguna Hotspot |
| `GET` | `/api.php?action=active` | Sesi aktif dengan konsumsi bandwidth real-time |
| `GET` | `/api.php?action=hosts` | Daftar host/perangkat terhubung |
| `GET` | `/api.php?action=vouchers` | Daftar voucher dari Database SQL/JSON |
| `POST` | `/api.php?action=generate_vouchers` | Generate batch voucher baru |
| `POST` | `/api.php?action=kick` | Putuskan koneksi user aktif |
| `POST` | `/api.php?action=bypass` | Bypass MAC perangkat tanpa login |
| `GET` | `/api.php?action=traffic` | Pantau trafik interface real-time |
| `POST` | `/api.php?action=find_id` | Cari internal .id dari nama item |
| `POST` | `/api.php?action=set_limit` | Ubah batas uptime / bytes akun hotspot |
| `POST` | `/api.php?action=set_comment` | Ubah komentar akun |
| `POST` | `/api.php?action=set_email` | Ubah email pengguna |
| `POST` | `/api.php?action=set_name` | Ganti nama / rename item |
| `POST` | `/api.php?action=pppoe_suspend` | Isolir pelanggan PPPoE |
| `POST` | `/api.php?action=pppoe_unsuspend` | Buka isolir pelanggan PPPoE |
| `POST` | `/api.php?action=generic_set` | Modifikasi properti dinamis pada path apapun |
| `POST` | `/api.php?action=generic_get` | Query fleksibel pada path apapun |
| `POST` | `/api.php?action=generic_remove` | Hapus item pada path apapun |

---

## 📋 Cheat Sheet Modul Lengkap

| Modul | Metode Populer |
| :--- | :--- |
| **`find()`** | `nameToId()`, `findname_toId()`, `byName()`, `byId()`, `byMac()`, `byIp()`, `where()`, `first()`, `exists()` |
| **`set()`** | `limit()`, `setLimit()`, `comment()`, `setComment()`, `email()`, `setEmail()`, `name()`, `setName()`, `password()`, `profile()`, `property()` |
| **`get()`** | `get()`, `getByName()`, `getById()`, `getValue()`, `count()`, `exists()`, `pluck()` |
| **`print()`** | `print()`, `printColumns()`, `printDetail()`, `printJson()`, `paginate()` |
| **`remove()`** | `remove()`, `byName()`, `byId()`, `where()`, `byComment()`, `byProfile()`, `all()` |
| **`toggle()`** | `enable()`, `disable()`, `toggle()`, `resetCounter()` |
| **`hotspot()`** | `getUsers()`, `addUserDetailed()`, `setUser()`, `setLimit()`, `setComment()`, `setEmail()`, `setName()`, `findNameToId()`, `enableUser()`, `disableUser()`, `getUserTraffic()`, `getTopDownloaders()`, `getTopActiveTime()`, `extendUserTime()`, `extendUserData()`, `lockUserToMac()`, `unlockUserMac()`, `getHotspotDhcpLeases()`, `getActiveTraffic()`, `disconnectUser()`, `bypassDevice()`, `blockDevice()`, `addWalledGardenDomain()`, `removeExpiredUsers()`, `syncToDatabase()` |
| **`voucher()`** | `generate()`, `generateAndSave()`, `generateQrCodeUrl()`, `getFromDb()`, `markUsed()`, `deleteBatch()`, `renderPrintCards()`, `renderThermalReceipt()`, `getSalesReport()`, `getBatchSummary()`, `exportToCsv()`, `importFromCsv()` |
| **`pppoe()`** | `getUsers()`, `addUser()`, `setUser()`, `setComment()`, `setPassword()`, `setProfile()`, `setName()`, `findNameToId()`, `enableUser()`, `disableUser()`, `removeUser()`, `getActive()`, `getUserTraffic()`, `getTopActiveTime()`, `disconnectUser()`, `suspendCustomer()`, `unsuspendCustomer()`, `bindCallerId()`, `unbindCallerId()`, `assignStaticIp()`, `setPppoeDueDate()`, `getOverdueCustomers()`, `autoIsolateOverdue()`, `ensureIsolateProfile()`, `syncToDatabase()` |
| **`firewall()`** | `getFilterRules()`, `addFilterRule()`, `getNatRules()`, `addMasquerade()`, `addAddressList()` |
| **`queue()`** | `getAll()`, `add()`, `limitBandwidth()`, `remove()` |
| **`interface()`** | `getAll()`, `getEthernet()`, `getBridges()`, `monitorTraffic()`, `enable()`, `disable()` |
| **`system()`** | `getResources()`, `getIdentity()`, `setIdentity()`, `getClock()`, `getLogs()`, `ping()`, `reboot()`, `getVersion()`, `getMajorVersion()`, `isV7()`, `isV6()` |
| **`ip()`** | `getAddresses()`, `addAddress()`, `getPools()`, `getDhcpLeases()`, `makeDhcpLeaseStatic()`, `getArpTable()` |
| **`backup()`** | `saveBackup()`, `exportConfig()`, `getFiles()`, `removeFile()` |
| **`tool()`** | `wakeOnLan()`, `sendEmail()`, `sendSms()`, `traceroute()`, `ipScan()`, `bandwidthTest()` |
| **`wireless()`** | `getConnectedClients()`, `setWifiPassword()`, `setSsid()`, `disconnectClient()` |
| **`vpn()`** | `getWireguardPeers()`, `addWireguardPeer()`, `removeWireguardPeer()`, `getL2tpServer()` |
| **`billing()`** | `handleWebhook()`, `generateVoucherForPayment()` |
| **`dnsFilter()`** | `blockDomain()`, `unblockDomain()`, `flushCache()`, `importHostsBlocklist()` |
| **`bot()`** | `handle($message, $sender)` |
| **`db()`** | `insert()`, `update()`, `delete()`, `select()`, `find()`, `count()`, `log()`, `query()`, `fetchAll()` |
| **`json()`** | `all()`, `get()`, `set()`, `push()`, `find()`, `where()`, `deleteWhere()`, `clear()` |
| **`cache()`** | `get()`, `set()`, `has()`, `remember()`, `forget()`, `clear()` |
| **`ip()`** | `getAddresses()`, `addAddress()`, `getPools()`, `getDhcpLeases()`, `makeDhcpLeaseStatic()`, `getArpTable()` |
| **`backup()`** | `saveBackup()`, `exportConfig()`, `getFiles()`, `removeFile()` |
| **`tool()`** | `wakeOnLan()`, `sendEmail()`, `sendSms()`, `traceroute()`, `ipScan()`, `bandwidthTest()` |
| **`wireless()`** | `getConnectedClients()`, `setWifiPassword()`, `setSsid()`, `disconnectClient()` |
| **`vpn()`** | `getWireguardPeers()`, `addWireguardPeer()`, `removeWireguardPeer()`, `getL2tpServer()` |
| **`billing()`** | `handleWebhook()`, `generateVoucherForPayment()` |
| **`dnsFilter()`** | `blockDomain()`, `unblockDomain()`, `flushCache()`, `importHostsBlocklist()` |
| **`bot()`** | `handle($message, $sender)` |
| **`db()`** | `insert()`, `update()`, `delete()`, `select()`, `find()`, `count()`, `log()`, `query()`, `fetchAll()` |
| **`json()`** | `all()`, `get()`, `set()`, `push()`, `find()`, `where()`, `deleteWhere()`, `clear()` |
| **`cache()`** | `get()`, `set()`, `has()`, `remember()`, `forget()`, `clear()` |

---

## 🖥️ Web Dashboard Interaktif

Jalankan server PHP bawaan untuk membuka antarmuka web:
```bash
php -S 0.0.0.0:8000
```
Buka browser pada: `http://localhost:8000`

---

## 📄 Lisensi & Kontributor

Project ini dirilis di bawah lisensi [MIT License](LICENSE).

Dibuat dengan ❤️ oleh **[Bang AL](https://github.com/comitIdn)**.
