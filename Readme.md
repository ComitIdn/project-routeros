<div align="center" id="top">
  <h1>⚡ Project RouterOS PHP Framework (Full Version)</h1>
  <p><b>Lightweight, Multi-Protocol &amp; Enterprise-Ready PHP API Framework for MikroTik RouterOS</b><br>
  Inspired by <a href="https://github.com/comitIdn/RouterOS-API">comitIdn/RouterOS-API</a> &bull; Full Support for <b>Multi-Protocol Transports (API, REST, SSH, Telnet)</b>, <b>Multi-PTMP Routeros Connection</b>, <b>Full CRUD Actions</b>, <b>SQL Database</b>, <b>JSON Storage</b>, <b>Web Apps</b>, <b>Python SDK</b>, <b>JavaScript SDK</b>, <b>Chat Bots</b>, and <b>Terminal CLI</b></p>
</div>

<p align="center">
  <img alt="PHP Version" src="https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?logo=php&logoColor=white">
  <img alt="RouterOS" src="https://img.shields.io/badge/RouterOS-v6%20%7C%20v7-E05A47?logo=mikrotik&logoColor=white">
  <img alt="Python SDK" src="https://img.shields.io/badge/Python-3.x%20SDK-3776AB?logo=python&logoColor=white">
  <img alt="JS SDK" src="https://img.shields.io/badge/JavaScript-ES6%20%7C%20Node-F7DF1E?logo=javascript&logoColor=black">
  <img alt="Transports" src="https://img.shields.io/badge/Transports-API%20%7C%20REST%20%7C%20SSH%20%7C%20Telnet-purple">
  <img alt="PTMP" src="https://img.shields.io/badge/Wireless-Multi--PTMP%20NV2-green">
  <img alt="Database" src="https://img.shields.io/badge/Database-SQLite%20%7C%20MySQL%20%7C%20JSON-003B57?logo=sqlite&logoColor=white">
  <img alt="License" src="https://img.shields.io/badge/License-MIT-green">
</p>

<p align="center">
  <a href="#-fitur-utama-framework">Fitur Utama</a> &nbsp;|&nbsp;
  <a href="#-struktur-arsitektur">Arsitektur</a> &nbsp;|&nbsp;
  <a href="#-multi-protocol-transports-api-rest-ssh-telnet">Transports</a> &nbsp;|&nbsp;
  <a href="#-full-operational-crud-actions-terpisah">CRUD Actions</a> &nbsp;|&nbsp;
  <a href="#-multi-ptmp-point-to-multipoint-wireless-management">PTMP Connection</a> &nbsp;|&nbsp;
  <a href="#-per-router-isolated-session--cache-arsitektur-bersih">Per-Router Session & Cache</a> &nbsp;|&nbsp;
  <a href="#-multi-logging-per-id-routeros-database--file-log">Multi-Logging</a> &nbsp;|&nbsp;
  <a href="#-multi-chatid-bot-telegram--whatsapp-alert">Multi ChatId Bot</a> &nbsp;|&nbsp;
  <a href="#-mikhmon-official-hotspot--pppoe-profiles-dan-auto-scheduler">Mikhmon Official Profiles</a> &nbsp;|&nbsp;
  <a href="#-mass-scale-voucher-engine-hingga-10000-sekaligus">Voucher Massal</a> &nbsp;|&nbsp;
  <a href="#-enterprise-server--firewall-security-baseline">Server & Firewall</a> &nbsp;|&nbsp;
  <a href="#-unified-mysql--mariadb--sqlite-architecture">MySQL Enterprise</a> &nbsp;|&nbsp;
  <a href="#-dukungan-multi-platform--bahasa-php-python-js-cli-json">Multi-Bahasa</a> &nbsp;|&nbsp;
  <a href="#-rest-api-json-endpoint">REST API</a> &nbsp;|&nbsp;
  <a href="#-cheat-sheet-modul-lengkap">Cheat Sheet</a>
</p>

---

## 🌟 Fitur Utama Framework

1. **Bebas Ketergantungan Monoton (Multi-Protocol Transports)**:
   - Tidak terkunci hanya pada port socket API binary (8728/8729).
   - Mendukung **REST HTTP/HTTPS API Native (RouterOS v7+)** via port 80 / 443 tanpa dependensi socket biner.
   - Mendukung **SSH CLI (Port 22)** dengan parser otomatis output terminal.
   - Mendukung **Telnet Socket (Port 23)** tanpa dependensi ekstensi eksternal.
   - **Auto-Transport Detection**: Secara cerdas mendeteksi protokol router yang aktif (API -> REST -> SSH -> Telnet).
2. **Full Operational Actions Terpisah (Superpower CRUD)**:
   - File terpisah, bersih tanpa penamaan ganda: **`Creator`**, **`Writer`**, **`Reader`**, **`Finder`**, **`Setter`**, **`Getter`**, **`Printer`**, **`Remover`**, **`Toggler`**.
   - Tambah record baru, jalankan script/terminal raw, paginasi data, cari `.id` dari nama, ubah limit/komentar/email/nama, toggle aktif/nonaktif dalam 1 baris.
3. **Multi-PTMP Routeros Connection Management (Untuk Tower & Base Station WISP / RT-RW Net)**:
   - Mendukung segala jenis perangkat MikroTik: **802.11a/b/g/n/ac**, **MikroTik NV2 TDMA**, **Nstreme / Dual Nstreme**, **60GHz (w60g)**, dan **WiFiWave2 / WiFi 6**.
   - Pantau seluruh CPE Station yang terhubung: Signal dBm (TX/RX), CCQ %, SNR, Throughput TX/RX, Jarak (km), Versi RouterOS, dan Status Kesehatan Link (*Health Index*).
   - Manajemen Access-List (Whitelist/Blacklist MAC dengan batas toleransi sinyal), Connect-List, Kick Station, Ganti Frekuensi, Channel Width, dan Pengaturan NV2.
4. **Per-Router Isolated Session & Cache (Bebas Tabrakan Data Multi-Router)**:
   - Sesi runtime dan token terisolasi aman per router ID di `database/sessions/router_{id}.json` dengan file locking atomik.
   - Cache partisi direktori terisolasi `database/cache/{routerId}/` dengan TTL cerdas (`$ros->cache()->forRouter($id)`).
5. **Multi Logging Per ID RouterOS (SQL Database & File Log Terpartisi)**:
   - Pencatatan aktivitas ganda: tersimpan rapih di database SQL (`activity_logs` dengan kolom `router_id`) dan file log fisik terpisah (`database/logs/router_{id}.log`).
   - Riwayat log dapat diekstraksi per router ID langsung via `$ros->getLogs($limit)` atau API REST.
6. **Multi ChatId Bot Telegram & WhatsApp Notifications**:
   - Mendukung pengiriman notifikasi broadcast alert ke banyak ID grup NOC, tim teknisi, dan pimpinan secara bersamaan.
   - Pengaturan mudah lewat file `config.php` (`telegram_chat_ids` & `whatsapp_recipients`) atau dinamis saat runtime (`addTelegramChatId`, `addWhatsAppRecipient`).
7. **Mikhmon Official Hotspot & PPPoE Profiles, Schedulers & Auto-Isolir**:
   - **Hotspot On-Login Script**: Format resmi Mikhmon (`exp: date time`), kunci MAC address otomatis (*anti-tethering*), dan scheduler cleaner penghapus sesi hantu.
   - **PPPoE Isolir Profile & Scheduler**: Profile `ISOLIR`, auto-isolir scheduler memeriksa tag jatuh tempo `due:YYYY-MM-DD`, firewall NAT redirect ke landing page isolir, dan metode reaktivasi instan pelanggan pasca bayar (`reconnectCustomer()`).
8. **Dukungan Multi-Bahasa & Lingkungan Pemrograman**:
   - **PHP**: Native / Manual, Laravel Facade & ServiceProvider, CodeIgniter 3 & 4.
   - **Python SDK (`routeros.py`)**: Standalone library Python 3 tanpa dependensi (zero-dependency).
   - **JavaScript SDK (`assets/js/routeros.js`)**: Universal UMD SDK untuk Browser, Vue, React, Svelte, dan Node.js.
   - **RESTful JSON API (`api.php`)**: Endpoint siap pakai dengan CORS untuk aplikasi Mobile & Web.
   - **Terminal CLI Console (`console.php`)**: Manajemen interaktif langsung dari terminal Linux/Termux.
9. **Dua Jenis Penyimpanan Database (SQL PDO & JSON Flat-File)**:
   - **SQLite** tanpa instalasi server database (*zero-config*) dan dukungan **MySQL / MariaDB / PostgreSQL**.
   - **JSON Flat-File Storage**: Simpan konfigurasi, voucher, dan log langsung sebagai file JSON.
10. **Mass-Scale Voucher Engine (Hingga 10.000+ Voucher Sekaligus)**:
   - Generate ribuan voucher dalam hitungan milidetik menggunakan engine *collision-free hash set*.
   - Multi-row chunked batch inserts ke MySQL / SQLite dalam 1 transaksi ACID, serta atomic push ke JSON storage.
   - Sinkronisasi single-roundtrip ke RouterOS via compact CLI script atau ekspor file `.rsc` siap impor.
11. **Enterprise Server Interfaces & Firewall Hardening**:
   - Khusus server data center & ISP (CCR, CRS, CHR, x86): Port Fiber SFP/SFP+ 10G/40G, LACP Bonding (802.3ad), VRRP Dual-Router HA Failover, 802.1Q VLANs, VXLAN, EoIP, GRE, WireGuard.
   - **FastTrack Acceleration**: Memangkas utilisasi CPU server hingga 80% pada throughput Gigabit/10G.
   - **Brute-Force Guard**: Karantina otomatis 3-tahap untuk port SSH (22), WinBox (8291), dan API (8728/8729).
   - **Port Scan Detection (PSD)**: Blokir otomatis pemindai jaringan (Nmap/Masscan).
12. **REST API Backend Hardening**:
   - Proteksi otentikasi opsional via Bearer Token atau header `X-API-KEY`.
   - Kompresi transfer **HTTP Gzip** otomatis (mengurangi ukuran payload JSON hingga 85-90%).
   - **Batch Multi-Operation Endpoint**: Eksekusi banyak operasi sekaligus dalam 1 request HTTP roundtrip.
   - Header telemetri latensi eksekusi backend (`X-Response-Time-Ms`).

---

## 🏗️ Struktur Arsitektur

```text
project-routeros/
├── config.example.php          # Template Konfigurasi Router & Database
├── config.php                  # Konfigurasi Lokal (Host, User, Pass, DB, Protocol, API Key)
├── composer.json               # Dependensi & Autoloading PSR-4 + Laravel Auto-Discovery
├── example.php                 # Master Contoh Implementasi Bersih & Profesional
├── routeros.py                 # Universal Python 3 SDK & Standalone CLI
├── routeros.js                 # Universal Master JavaScript SDK (Node.js & Browser)
├── mikrotik.php                # Alias Kompatibilitas ke Belakang (Forwarder ke example.php)
├── console.php                 # CLI Console Tool Interaktif untuk Terminal / Termux
├── api.php                     # Universal RESTful JSON API Endpoint (CORS, Gzip, Auth, Batch)
├── bot_webhook.php             # Webhook Handler Telegram & WhatsApp Bot
├── index.php                   # Web Dashboard & Generator Voucher Interaktif
├── examples/                   # Direktori Contoh Kode Modular & Terstruktur
│   ├── 01_quickstart.php       # Inisialisasi koneksi, resource, ping & version
│   ├── 02_hotspot.php          # Hotspot user, limits, traffic realtime, on-login profile
│   ├── 03_voucher.php          # Bulk voucher 10.000+, QR Code, print card, thermal printer
│   ├── 04_pppoe.php            # PPPoE secrets, static IP, billing overdue & auto-isolate
│   ├── 05_actions.php          # Dedicated Actions: Finder, Setter, Getter, Printer, Remover
│   ├── 06_multi_platform.php   # Panduan integrasi Laravel, CodeIgniter, JS SDK, CLI
│   ├── 07_ptmp_and_protocols.php # Multi-Protocol Transports & Multi-PTMP Routeros Connection
│   ├── 08_javascript_master.js # Master JavaScript Multi-Router Concurrent Pool & Debugger
│   ├── 09_enterprise_server_and_security.php # SFP Fiber, LACP Bonding, VRRP, & FastTrack Firewall
│   ├── 10_mysql_enterprise_architecture.php  # High-Throughput MySQL Batch Inserts & ACID Transactions
│   └── 11_mikhmon_profiles_and_multi_router.php # Mikhmon Official Profiles, Schedulers, Multi-Logging & Multi-Bot
├── assets/
│   └── js/
│       └── routeros.js         # Client-side JavaScript SDK Universal (Vanilla, Vue, React, Node)
├── database/
│   ├── schema.sql              # Skema SQL (routers, vouchers, users, activity_logs)
│   ├── routeros.sqlite         # Database SQLite lokal (dibuat otomatis)
│   ├── json/                   # Direktori penyimpanan database flat JSON
│   ├── cache/                  # Direktori cache response MikroTik (TTL per-router)
│   ├── sessions/               # Penyimpanan sesi terisolasi per-router (router_{id}.json)
│   └── logs/                   # Log aktivitas fisik terpartisi per router (router_{id}.log)
├── src/
│   ├── Transports/             # Multi-Protocol Transport Drivers
│   │   ├── TransportInterface.php # Kontrak Transport Universal
│   │   ├── ApiTransport.php       # Binary API Driver (Port 8728 / 8729 SSL)
│   │   ├── RestTransport.php      # RouterOS v7+ Native REST HTTP/HTTPS Driver (Port 80 / 443)
│   │   ├── SshTransport.php       # SSH CLI Terminal Driver (Port 22)
│   │   ├── TelnetTransport.php    # Raw Socket Telnet Driver (Port 23)
│   │   └── AutoTransport.php      # Auto-Discovery Driver (Deteksi Port Terbuka)
│   ├── Actions/                # File Operasional Terpisah & Bersih (Superpower CRUD)
│   │   ├── Creator.php         # Tambah data: IP, User, PPPoE, Queue, Access-List, VLAN
│   │   ├── Writer.php          # Eksekusi command, script dinamis, dan Terminal CLI raw
│   │   ├── Reader.php          # Pembacaan data, streaming wireless, dan monitoring traffic
│   │   ├── Finder.php          # Pencarian .id dari name (findNameToId), byName, byId, byMac, byIp
│   │   ├── Setter.php          # Pengaturan granular: setLimit, setComment, setEmail, setName
│   │   ├── Getter.php          # Pengambilan data fleksibel: getByName, getValue, count, pluck
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
│   │   ├── Connection.php      # Transport Manager dengan error recovery & dynamic switching
│   │   ├── RouterSession.php   # Sesi & Token Terisolasi Per-Router dengan Atomic File Locking
│   │   ├── Database.php        # Lightweight SQL PDO Engine (SQLite, MySQL, Multi-Logging)
│   │   ├── JsonStorage.php     # Engine Database File JSON Flat-Store
│   │   ├── Cache.php           # High-Performance TTL Caching Layer (Partisi Per-Router)
│   │   ├── MultiRouter.php     # Enterprise Multi-Router Manager
│   │   ├── Response.php        # Standardized JSON Response Formatter
│   │   └── Helper.php          # Utilitas format bytes, validasi IP/MAC, PIN acak
│   └── Modules/
│       ├── Ptmp.php            # Multi-PTMP Routeros Connection: NV2, Nstreme, 60GHz, Sinyal, CCQ, Access-List
│       ├── Hotspot.php         # Manajemen Hotspot lengkap (User, Active, Host, Bypass)
│       ├── Voucher.php         # Generator Voucher, Simpan SQL/JSON, Cetak Kupon
│       ├── PPPoE.php           # Manajemen PPPoE (Secret, Profile, Active, Server, Isolir)
│       ├── Firewall.php        # Filter, NAT Masquerade, Mangle, RAW, Address List
│       ├── Queue.php           # Simple Queue & Bandwidth Limiter otomatis
│       ├── NetworkInterface.php# Enterprise Server: SFP/SFP+ Fiber, LACP Bonding, VRRP Failover, VLAN, WireGuard, Traffic
│       ├── System.php          # Resources, Clock, Reboot, Ping, Script, Scheduler, v6/v7 Check
│       ├── Ip.php              # Addresses, Pools, DHCP Server, DHCP Lease, ARP, Static DNS
│       ├── Backup.php          # Binary Backup (.backup) & Export Konfigurasi (.rsc)
│       ├── Tool.php            # Wake-on-LAN, Email, SMS, Traceroute, IP Scan, Bandwidth Test
│       ├── Vpn.php             # WireGuard Peers & Interface, L2TP, OpenVPN, SSTP
│       ├── Billing.php         # Payment Gateway Webhook (Tripay, Midtrans, QRIS)
│       ├── DnsFilter.php       # DNS AdBlocker & Domain Redirect Blacklist
│       └── BotHandler.php      # Parser Perintah Bot Telegram & WhatsApp Gateway
└── RouterOSConnection.php      # Wrapper kompatibel ke belakang (Backward-compatible)
```

---

## 🌐 Multi-Protocol Transports (API, REST, SSH, Telnet)

Anda tidak lagi terbatas hanya pada port socket API biner (8728). Jika port API ditutup firewall ISP atau dinonaktifkan, Anda dapat beralih ke REST HTTP/HTTPS, SSH CLI, atau Telnet secara instan:

```php
use RouterOSFramework\Core\RouterOS;

// 1. Koneksi REST Native (RouterOS v7+) via HTTP (80) / HTTPS (443)
$ros = RouterOS::connect([
    'host'     => '192.168.88.1',
    'user'     => 'admin',
    'pass'     => 'password',
    'protocol' => 'rest', // Menggunakan REST JSON bawaan RouterOS v7
]);

// 2. Koneksi Terminal SSH (Port 22)
$ros = RouterOS::connect([
    'host'     => '192.168.88.1',
    'user'     => 'admin',
    'pass'     => 'password',
    'protocol' => 'ssh',
]);

// 3. Koneksi Telnet Socket (Port 23)
$ros = RouterOS::connect([
    'host'     => '192.168.88.1',
    'user'     => 'admin',
    'pass'     => 'password',
    'protocol' => 'telnet',
]);

// 4. Auto-Detect: Otomatis mendeteksi port mana yang terbuka
$ros = RouterOS::connect([
    'host'     => '192.168.88.1',
    'user'     => 'admin',
    'pass'     => 'password',
    'protocol' => 'auto',
]);

// Ganti protokol saat runtime:
$ros->useRest();   // Beralih ke REST
$ros->useSsh();    // Beralih ke SSH
$ros->useTelnet(); // Beralih ke Telnet
$ros->useApi();    // Beralih ke Binary API
```

---

## 🛠️ Full Operational CRUD Actions Terpisah

Setiap aksi operasional berada dalam class PHP independen di [`src/Actions/`](file:///data/data/com.termux/files/home/project-routeros/src/Actions/), rapi, cepat, dan tanpa fungsi dobel:

| Action Class | Akses Metode | Fungsi & Kemampuan Utama |
| :--- | :--- | :--- |
| **`Creator`** | `$ros->create()` / `$ros->creator()` | `create()`, `add()`, `createIfNotExists()`, `batchCreate()`, `ipAddress()`, `hotspotUser()`, `pppoeSecret()`, `queueSimple()`, `firewallRule()`, `natRule()`, `ptmpAccessList()`, `vlan()` |
| **`Writer`** | `$ros->write()` / `$ros->writer()` | `write()`, `raw()`, `terminal()`, `batch()`, `runScript()`, `executeScriptSource()` |
| **`Reader`** | `$ros->read()` / `$ros->reader()` | `read()`, `first()`, `monitorTraffic()`, `stats()`, `streamWireless()` |
| **`Finder`** | `$ros->find()` / `$ros->finder()` | `nameToId()`, `findname_toId()`, `byName()`, `byId()`, `byMac()`, `byIp()`, `where()`, `first()`, `exists()` |
| **`Setter`** | `$ros->set()` / `$ros->setter()` | `set()`, `limit()`, `comment()`, `email()`, `name()`, `password()`, `profile()`, `property()` |
| **`Getter`** | `$ros->get()` / `$ros->getter()` | `get()`, `getByName()`, `getById()`, `getValue()`, `count()`, `exists()`, `pluck()` |
| **`Printer`** | `$ros->print()` / `$ros->printer()` | `print()`, `printColumns()`, `printDetail()`, `printJson()`, `paginate()` |
| **`Remover`** | `$ros->remove()` / `$ros->remover()` | `remove()`, `byName()`, `byId()`, `where()`, `batch()`, `byComment()`, `byProfile()`, `all()` |
| **`Toggler`** | `$ros->toggle()` / `$ros->toggler()` | `enable()`, `disable()`, `toggle()`, `enableBatch()`, `disableBatch()`, `resetCounter()` |

#### Contoh Penggunaan Action Lengkap:
```php
// 1. CREATOR: Tambah entitas baru
$ros->create()->ipAddress('192.168.50.1/24', 'ether2', 'Gateway Hotspot');
$ros->create()->hotspotUser('siswa01', 'sandi123', 'paket-2mbps');
$ros->create()->queueSimple('Limit-Siswa', '192.168.50.0/24', '10M/10M');

// 2. READER: Baca data & statistik traffic
$users = $ros->read()->read('/ip/hotspot/user', ['profile' => 'default']);
$traffic = $ros->read()->stats('ether1'); // rx_bytes, tx_bytes, packets, drops

// 3. WRITER: Eksekusi Terminal CLI raw atau script dinamis
$cliOutput = $ros->write()->terminal('/interface wireless print detail');

// 4. FINDER: Cari internal .id dari nama
$id = $ros->findNameToId('siswa01', '/ip/hotspot/user'); // Hasil: '*2B'

// 5. SETTER: Modifikasi granular tanpa load seluruh objek
$ros->setLimit('siswa01', '3h', '2G');           // Set uptime + byte limit
$ros->setComment('siswa01', 'Siswa Kelas XII');  // Set comment
$ros->setEmail('siswa01', 'siswa@sekolah.sch.id'); // Set email
$ros->setName('siswa01', 'siswa_baru');          // Rename aman

// 6. TOGGLER: Disable / Enable dalam 1 baris
$ros->toggle()->toggle('/ip/hotspot/user', 'siswa_baru');
```

---

## 📡 Multi-PTMP (Point-to-Multipoint) Wireless Management

Modul khusus [`$ros->ptmp()`](file:///data/data/com.termux/files/home/project-routeros/src/Modules/Ptmp.php) dirancang untuk operator Tower BTS WISP, RT-RW Net, dan Distribusi Wireless Multi-Klien:

```php
// 1. Deteksi hardware wireless router (Legacy, NV2, 60GHz w60g, atau WiFiWave2)
$hw = $ros->ptmp()->detectHardware();

// 2. Ambil daftar seluruh CPE station yang terhubung beserta analisis sinyal lengkap
$stations = $ros->ptmp()->getStations('wlan1');
foreach ($stations as $cpe) {
    echo "MAC: {$cpe['mac_address']} | Sinyal: {$cpe['signal_strength']} | CCQ: {$cpe['ccq_tx']} | Jarak: {$cpe['distance']} | Status: {$cpe['health']}\n";
}

// 3. Ringkasan Kesehatan Jaringan PTMP (Health Index)
$metrics = $ros->ptmp()->getStationQualityMetrics('wlan1');
// ['total_stations' => 24, 'average_signal' => '-64 dBm', 'degraded_count' => 1, 'health_percentage' => 95.8]

// 4. Manajemen Access-List (Whitelist MAC dengan batasan toleransi sinyal agar radio tidak drop)
$ros->ptmp()->addAccessList('00:0C:42:AA:BB:CC', 'wlan1', '-80..120', 'Pelanggan Rumah Bpk Joko', true);

// 5. Kick / Disconnect Station CPE tertentu
$ros->ptmp()->kickStation('00:0C:42:AA:BB:CC');

// 6. Set Frekuensi & Channel Width AP Tower
$ros->ptmp()->setChannel('wlan1', 5200, '5ghz-only-n', '20/40mhz-XX');

// 7. Konfigurasi Protokol MikroTik NV2 TDMA (Solusi Hidden Node Problem)
$ros->ptmp()->setProtocol('wlan1', 'nv2');
$ros->ptmp()->setNv2Config('wlan1', [
    'nv2-cell-radius'      => 30, // 30 km
    'nv2-tdma-period-size' => 2,  // 2 ms
    'nv2-security'         => 'key-management',
    'nv2-preshared-key'    => 'KunciRahasiaNV2Tower!',
]);
```

---

## 🎫 Mass-Scale Voucher Engine (Hingga 10.000+ Sekaligus)

Modul [`$ros->voucher()`](file:///data/data/com.termux/files/home/project-routeros/src/Modules/Voucher.php) dirancang untuk kebutuhan RT/RW Net, ISP, dan Event Organizer berskala raksasa yang membutuhkan ribuan voucher dalam hitungan detik tanpa membebani CPU router:

```php
// 1. Generate 5.000 s/d 10.000 Voucher Massal ke Database (Selesai dalam < 1 Detik)
$batch = $ros->voucher()->generateFastBulk(5000, [
    'prefix'       => 'VC-',
    'length'       => 6,
    'characters'   => 'alphanumeric_upper', // Huruf kapital & angka jelas
    'profile'      => '1HARI-5MB',
    'limit_uptime' => '1d',
    'price'        => 5000,
    'batch_tag'    => 'event-merdeka',
    'sync_mode'    => 'stock_only', // Disimpan di database SQL sebagai stok voucher siap jual
    'save_db'      => true,
]);

echo "Generated: {$batch['generated']} vouchers dalam {$batch['duration_ms']} ms\n";

// 2. Ekspor File Script RouterOS (.rsc) Siap Impor
$rscContent = $ros->voucher()->generateRscScript($batch['vouchers']);
file_put_contents('database/exports/vouchers.rsc', $rscContent);

// 3. Ambil data voucher terindeks & cetak kupon kartu thermal / QR Code
$vouchers = $ros->voucher()->getFromDb(['batch_tag' => 'event-merdeka', 'limit' => 50]);
$htmlCards = $ros->voucher()->renderPrintCards($vouchers, [
    'title'    => 'HOTSPOT WARNET CYBER',
    'dns_name' => 'http://wifi.kafe/login',
    'price'    => 'Rp 5.000 / 1 Hari',
    'show_qr'  => true,
]);
```

---

## 🔐 Profil Hotspot On-Login Script Otomatis (Auto-Expiry & Anti-Tethering)

Modul [`$ros->hotspot()`](file:///data/data/com.termux/files/home/project-routeros/src/Modules/Hotspot.php) memprogram otomatis script `on-login` RouterOS yang kompatibel dengan RouterOS v6 dan v7:

```php
// 1. Setup Profil Voucher ISP dengan On-Login Otomatis
$ros->hotspot()->createVoucherProfile('1HARI-5MB', '2M/5M', '1d', [
    'shared_users'   => '1',
    'lock_mac'       => true,  // Kunci otomatis ke MAC HP saat login pertama kali (Anti-Sharing)
    'track_expiry'   => true,  // Catat tanggal & jam login pertama di comment, hapus sesi saat expired
    'telegram_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', // Opsional notif Telegram
    'telegram_chat_id' => '987654321',
]);

// 2. Pasang Background Cleaner di RouterOS untuk menghapus sesi mati / ghost sessions
$ros->hotspot()->installExpiryCleanupScheduler(5); // Jalan otomatis tiap 5 menit
```

---

## ⚡ Per-Router Isolated Session & Cache (Arsitektur Bersih)

Saat mengelola puluhan router MikroTik secara bersamaan, tabrakan data (*session collision*) sering terjadi jika session dan cache tidak terisolasi. Framework ini menyediakan sistem partisi runtime terisolasi per-router ID:

### 1. Sesi Terisolasi Per-Router ([`RouterSession`](file:///data/data/com.termux/files/home/project-routeros/src/Core/RouterSession.php))
Tiap router memiliki file session mandiri di `database/sessions/router_{id}.json` lengkap dengan token autentikasi, status keaktifan, dan kunci atomic file lock:

```php
// Inisialisasi router spesifik
$ros->setRouterId('tower-utara');
$session = $ros->session();

// Simpan runtime state router
$session->set('admin_user', 'superadmin');
$session->set('selected_frequency', '5180');

// Baca atribut session
$user = $session->get('admin_user');
$token = $session->getToken();
$isValid = $session->isValid(3600); // Cek apakah sesi aktif dalam 1 jam
```

### 2. Cache Terpartisi Per-Router ([`Cache`](file:///data/data/com.termux/files/home/project-routeros/src/Core/Cache.php))
Data cache disimpan dalam folder terpisah `database/cache/{routerId}/` sehingga data query router A tidak akan pernah tertukar dengan router B:

```php
$cache = $ros->cache(); // Otomatis terikat ke router ID saat ini

// Simpan cache dengan durasi TTL (detik)
$cache->set('active_stations', $stations, 120);

// Atau akses cache router lain secara dinamis
$southCache = $cache->forRouter('tower-selatan')->get('active_stations');

// Bersihkan cache router spesifik saja tanpa menghapus cache router lainnya
$cache->clearRouter('tower-utara');
```

---

## 📝 Multi-Logging Per ID RouterOS (Database & File Log)

Pencatatan aktivitas sistem terintegrasi ganda:
1. **SQL Database (`activity_logs`)**: Terindeks dengan kolom `router_id` untuk pencarian dan audit instan.
2. **File Log Fisik Terpisah (`database/logs/router_{id}.log`)**: Log berputar (*file append*) yang aman diekspor untuk analisa NOC.

```php
// Catat aktivitas router
$ros->log('BILLING', 'Auto-Isolir', 'client_andi', 'Customer diisolir karena jatuh tempo', 'warning');
$ros->log('SECURITY', 'Firewall Drop', '192.168.88.200', 'Brute force port 8291 terdeteksi', 'danger');

// Ambil riwayat log per router ID (Max 20 data terakhir)
$logs = $ros->getLogs(20);
foreach ($logs as $l) {
    echo "[{$l['timestamp']}] ({$l['category']}) {$l['action']} -> {$l['details']}\n";
}
```

---

## 🤖 Multi ChatId Bot Telegram & WhatsApp Alert

Notifikasi alert jaringan kini dapat didistribusikan ke banyak penerima sekaligus (misal: grup NOC, teknisi lapangan, dan pimpinan ISP):

```php
$bot = $ros->bot();

// Tambah ID chat Telegram (grup atau personal)
$bot->addTelegramChatId('-1001234567890'); // Grup NOC Telegram
$bot->addTelegramChatId('987654321');      // Superadmin Personal

// Tambah Nomor WhatsApp (individu atau ID grup)
$bot->addWhatsAppRecipient('628123456789');  // Admin WhatsApp
$bot->addWhatsAppRecipient('628987654321');  // Teknisi On-Duty

// Broadcast pesan alert ke seluruh channel sekaligus
$res = $bot->broadcast("🚨 [ALERT] Router " . $ros->getRouterId() . ": Interface sfp-sfpplus1 LINK DOWN!");
echo "Terkirim ke {$res['telegram']['sent']} grup Telegram & {$res['whatsapp']['sent']} nomor WhatsApp\n";
```

Konfigurasi statis juga dapat langsung disetel di [`config.php`](file:///data/data/com.termux/files/home/project-routeros/config.php):
```php
'bot' => [
    'telegram_chat_ids'   => ['-1001234567890', '987654321'],
    'whatsapp_recipients' => ['628123456789', '628987654321'],
],
```

---

## 👑 Mikhmon Official Hotspot & PPPoE Profiles dan Auto-Scheduler

Dukungan 100% kompatibel dan otentik dengan alur kerja **Mikhmon Online Official** untuk manajemen billing ISP RT/RW Net:

### 1. Mikhmon Hotspot User Profile, MAC-Cookie & Schedulers
Menciptakan profil voucher dengan script `on-login` resmi Mikhmon yang mencatat jam login pertama (`exp: date time`), sistem kalender otomatis (kabisat & pergantian tahun), penguncian MAC address (*anti-tethering*), MAC cookie timeout, dan pencatatan riwayat penjualan di `/system script`:

```php
// Contoh 1: Profil "V15D" (15 Hari, Pool hs-pool-22, MAC Cookie 1d, 3M/2M, Jual Rp 10.000, Mode ntfc / N)
$ros->hotspot()->setupMikhmonProfile(
    'V15D',       // Nama profil
    '3M/2M',      // Rate limit (rx/tx)
    '15d',        // Masa aktif
    0,            // Harga modal
    10000,        // Harga jual (Rp 10.000)
    'ntfc',       // Mode expired: 'ntfc' (Notice & Limit 1s), 'remc' (Remove & Record), 'rem', 'ntf'
    'Disable',    // Lock user device MAC ('Disable' atau 'Enable')
    'none',       // Parent queue ('none' atau nama simple queue)
    'hs-pool-22', // Address pool (otomatis dibuat jika belum ada)
    '1',          // Shared users
    [
        'pool_ranges'         => '175.12.0.1-175.12.10.0, 175.12.10.2-175.12.255.254',
        'add_mac_cookie'      => 'yes',
        'mac_cookie_timeout'  => '1d 00:00:00',
        'insert_queue_before' => 'bottom',
        'install_schedulers'  => true, // Auto pasang 5 core schedulers Mikhmon
    ]
);

// Contoh 2: Profil "V24h" (24 Jam, 3M/2M, Beli Rp 8.000, Jual Rp 10.000, Mode remc / R)
$ros->hotspot()->setupMikhmonProfile(
    'V24h', '3M/2M', '24h', 8000, 10000, 'remc', 'Disable', 'none', 'none', '1'
);

// Contoh 3: Profil "V30D" (30 Hari Bulanan, 3M/2M, Rp 0 / Rp 150.000, Mode ntfc / N)
$ros->hotspot()->setupMikhmonProfile(
    'V30D', '3M/2M', '30d', 0, 150000, 'ntfc', 'Disable', 'none', 'none', '1'
);

// Pilihan Pool & Queue untuk Dropdown UI:
$pools = $ros->hotspot()->getPoolList();           // ['none', 'hs-pool-22', 'dhcp-pool-lan']
$queues = $ros->hotspot()->getParentQueueList();   // ['none', 'Total-Hotspot-50M']

// Dapatkan command teks MikroTik CLI siap copy-paste:
$cliCommand = $ros->hotspot()->generateMikhmonProfileCliCommand(
    'V15D', '3M/2M', '15d', 0, 10000, 'ntfc', 'Disable', 'none', 'hs-pool-22', '1',
    ['pool_ranges' => '175.12.0.1-175.12.10.0, 175.12.10.2-175.12.255.254']
);
```

### 2. Mikhmon PPPoE Customer Profile (P3), Isolir Profile, Pool & Scheduler
Menyiapkan profil pelanggan PPPoE lengkap dengan script `on-up` Mikhmon (kalkulasi kadaluarsa saat status comment `lunas`), IP pool `isolir`, profil `isolir` (512k/512k), dan scheduler RouterOS otomatis yang memutus koneksi saat jatuh tempo:

```php
// Setup Profil Pelanggan "P3" (Otomatis melengkapi Pool isolir, Profile isolir & Scheduler isolir)
$ros->pppoe()->setupMikhmonCustomerProfile(
    'P3',             // Nama profil pelanggan
    '3M/3M',          // Rate limit
    '192.168.100.1',  // Local Address Gateway
    'isolir',         // Remote Address (Pool isolir)
    '30d',            // Masa berlaku paket
    [
        'parent_queue' => 'none',
        'dns_server'   => '8.8.8.8,8.8.4.4',
        'auto_isolir'  => true // Auto-generate pool isolir, profile isolir & scheduler isolir
    ]
);

// Dapatkan CLI Script MikroTik Lengkap (Pool + Profiles + Scheduler):
$pppCli = $ros->pppoe()->generateMikhmonPppCliCommands('P3', '3M/3M', '192.168.100.1', 'isolir', '30d');

// Alur Pembayaran & Reaktivasi PPPoE:
// 1. Tagihan baru terbit -> Comment diatur: "lunas Paket 30 Hari"
// 2. Pelanggan connect -> Script on-up otomatis menghitung 30 hari ke depan & update comment: "$exp | Paket 30 Hari"
// 3. Scheduler 'isolir' mengecek comment setiap jam. Jika $expd < $today:
//    -> Profile diubah ke 'isolir' (512k/512k)
//    -> Comment diubah: "BELUM BAYAR | Exp: ... | pelanggan: P3"
//    -> Sesi aktif di /ppp active otomatis di-kick!
// 4. Pelanggan bayar -> Cukup set comment kembali ke "lunas ..." dan kembalikan profil ke 'P3'.
```

### 3. Enam Mesin Scheduler Otomatis Mikhmon (Auto-Generated)
Framework otomatis menginjeksi 6 scheduler resmi Mikhmon ke MikroTik RouterOS tanpa perlu pengaturan manual:

| Nama Scheduler | Interval | Start Time | Fungsi Utama |
|---|---|---|---|
| **`autoremove-report`** | `1d 00:00:00` | `startup` | Menghapus script laporan penjualan `/system script` yang berusia lebih dari 3 bulan |
| **`Expire-Monitor`** | `00:01:00` | `00:00:00` | Memonitor status sertifikat `expireMonitor`, memeriksa tanggal kadaluarsa voucher, limit 1s (mode N) / hapus user (mode R), dan kick `/ip hotspot active` |
| **`Live-Report-Monitor`** | `00:02:00` | `startup` | Menghitung omset pendapatan harian dan bulanan secara *real-time* via sertifikat RouterOS tanpa membebani disk/NAND |
| **`Reset-Expire-Monitor`** | `00:00:00` | `startup` | Memulihkan jam RouterOS pasca-reboot dari timestamp sertifikat untuk RouterBOARD tanpa baterai RTC |
| **`Reset-Expire-Monitor-Daily`** | `1d 00:00:00` | `00:00:00` | Mereset sertifikat `expireMonitor` harian agar status counter selalu bersih dan akurat |
| **`isolir`** | `01:00:00` | `startup` | Memonitor jatuh tempo `/ppp secret`, memindahkan pelanggan menunggak ke profil `isolir`, dan mendiskoneksikan koneksi aktif |

### 4. 1-Klik Bootstrap Seluruh Ekosistem Mikhmon
```php
// Cukup 1 baris untuk menyiapkan seluruh infrastruktur Mikhmon di router baru:
$ros->setupMikhmonAll([
    'hotspot_profile' => ['name' => 'V15D', 'rate_limit' => '3M/2M', 'validity' => '15d', 'address_pool' => 'hs-pool-22'],
    'pppoe_profile'   => ['name' => 'P3', 'rate_limit' => '3M/3M', 'validity' => '30d', 'remote_address' => 'isolir']
]);
```

---

## 🛡️ Enterprise Server & Firewall Security Baseline

Modul [`$ros->interface()`](file:///data/data/com.termux/files/home/project-routeros/src/Modules/NetworkInterface.php) dan [`$ros->firewall()`](file:///data/data/com.termux/files/home/project-routeros/src/Modules/Firewall.php) disesuaikan untuk Server MikroTik Enterprise (CCR, CRS, CHR, x86, RB1100, RB5009):

```php
// 1. Monitoring Port Server Fisik & Virtual
$sfpPorts = $ros->interface()->getSfp();       // SFP / SFP+ 10G/40G Fiber Optic
$bonding  = $ros->interface()->getBonding();   // Link Aggregation LACP (802.3ad)
$vrrp     = $ros->interface()->getVrrp();      // Dual-Router High-Availability Failover
$vlans    = $ros->interface()->getVlans();     // Trunking 802.1Q
$traffic  = $ros->interface()->monitorTraffic('sfp-sfpplus1'); // Live byte rate & bandwidth

// 2. 1-Click Server Security Hardening Baseline
// Mengaktifkan FastTrack (80% CPU offload), Drop Invalid, Brute-Force Guard, & Port Scanner Drop
$ros->firewall()->hardenServerBaseline('bridge', 'ether1');

// Atau konfigurasi aturan granular mandiri:
$ros->firewall()->enableFastTrack();
$ros->firewall()->protectBruteForce([22, 8291, 8728, 8729]); // Karantina brute-force 7 hari
$ros->firewall()->protectPortScanners();                     // Deteksi PSD nmap/masscan
```

---

## 🗄️ Unified MySQL / MariaDB & SQLite Architecture

Class [`$ros->db()`](file:///data/data/com.termux/files/home/project-routeros/src/Core/Database.php) menyediakan API seragam untuk **SQLite** dan **MySQL / MariaDB** dengan kemampuan transaksi ACID dan batch insert multi-baris:

```php
// 1. Inisialisasi Database (bisa via config.php atau runtime)
$db = $ros->db();

// 2. High-Speed Batch Insert (1.000 baris dalam < 100 ms)
$db->insertBatch('vouchers', $voucherRows, 500);

// 3. Transaksi Database Aman (ACID Transactions)
$db->transaction(function ($tx) use ($userId) {
    $tx->update('pppoe_users', ['status' => 'active'], 'id = :id', ['id' => $userId]);
    $tx->log('PPPoE', 'Reactivate Customer', $userId, 'Payment received');
});

// 4. Metrik Operasional Database
$stats = $db->stats();
// ['driver' => 'mysql', 'vouchers' => 15200, 'hotspot_users' => 450, 'pppoe_users' => 120]
```

---

## 🔌 Dukungan Multi-Platform & Bahasa (PHP, Python, JS, CLI, JSON)

### 1. Python SDK ([`routeros.py`](file:///data/data/com.termux/files/home/project-routeros/routeros.py))
Library Python 3 mandiri tanpa dependensi tambahan (*zero external dependencies*):

```python
from routeros import RouterOS

# Koneksi langsung via REST HTTP/HTTPS (RouterOS v7+)
ros = RouterOS(host="192.168.88.1", user="admin", password="password", protocol="rest")

# Atau koneksi via Web API endpoint
ros = RouterOS.via_api("http://localhost/project-routeros/api.php")

# Full CRUD Actions
ros.create("/ip/address", {"address": "10.10.10.1/24", "interface": "ether2"})
users = ros.read("/ip/hotspot/user")
ros.set("/ip/hotspot/user", "budi", {"limit-uptime": "2h", "comment": "VIP"})
ros.remove("/ip/hotspot/user", "budi")

# PTMP Wireless
stations = ros.ptmp.get_stations()
ros.ptmp.kick_station("00:0C:42:AA:BB:CC")
ros.ptmp.set_channel("wlan1", 5180)

# Hotspot & PPPoE
ros.hotspot.set_limit("budi", limit_uptime="2h")
ros.pppoe.suspend("client01")
```

Eksekusi via terminal:
```bash
python routeros.py status
python routeros.py ptmp stations
python routeros.py read /ip/hotspot/user
```

---

### 2. Universal JavaScript & Node.js SDK ([`routeros.js`](file:///data/data/com.termux/files/home/project-routeros/routeros.js) & [`assets/js/routeros.js`](file:///data/data/com.termux/files/home/project-routeros/assets/js/routeros.js))
Class JavaScript modern mandiri (*zero external npm dependencies*), mendukung **Node.js (CommonJS/ESM)**, **Browser (Vue, React, Svelte)**, dan **Next.js**:

#### A. Konfigurasi 1 Tempat (.env atau Config Object):
```javascript
const { RouterOS } = require('./routeros.js');

// Pilihan 1: Load otomatis dari file .env lokal (Zero-config)
const ros = RouterOS.fromEnv('.env');

// Pilihan 2: Inisialisasi terpusat dengan Built-in Debugger & Benchmark Timer
const ros = new RouterOS({
    host: '192.168.88.1',
    user: 'admin',
    password: 'password',
    protocol: 'rest',    // REST Native v7+ (Port 80/443) atau 'web_api'
    timeout: 5000,
    debug: true,         // Aktifkan Built-in Debugger mandiri
    logLevel: 'debug',   // 'trace' | 'debug' | 'info' | 'warn' | 'error'
    benchmark: true,     // Benchmark timer per query dalam milidetik (ms)
});
```

#### B. Multi-Router Concurrent Connection Pool (Koneksi Simultan Paralel):
```javascript
// Daftarkan multiple router ke dalam pool
ros.multi
    .add('router_pusat', { host: '10.10.1.1', user: 'admin', password: 'xxx' })
    .add('tower_utara',  { host: '10.10.2.1', user: 'admin', password: 'xxx' })
    .add('tower_selatan', { host: '10.10.3.1', user: 'admin', password: 'xxx' });

// 1. Query serentak secara bersamaan tanpa saling tunggu (Promise.allSettled)
const allUsers = await ros.multi.concurrentRead('ip/hotspot/user');

// 2. Health check serentak untuk memantau status CPU, latency, & uptime semua router
const health = await ros.multi.healthCheck();
console.table(health);
```

#### C. Operasi CRUD & PTMP Wireless dari JavaScript:
```javascript
// CRUD Primitives
await ros.create('ip/address', { address: '10.0.0.1/24', interface: 'ether2' });
const users = await ros.read('ip/hotspot/user', { profile: 'default' });
await ros.set('ip/hotspot/user', 'budi', { 'limit-uptime': '2h' });
await ros.remove('ip/hotspot/user', 'budi');

// PTMP Wireless & Sinyal CPE
const stations = await ros.ptmp.getStations('wlan1');
await ros.ptmp.kickStation('00:0C:42:AA:BB:CC');
await ros.ptmp.setChannel('wlan1', 5180, '5ghz-only-n');
```

Jalankan skrip demo:
```bash
node examples/08_javascript_master.js
```

---

### 3. Laravel Framework (8.x, 9.x, 10.x, 11.x+)
Otomatis terdaftar melalui Laravel Package Auto-Discovery:
```php
use RouterOS;

class RouterController extends Controller
{
    public function index()
    {
        $users = RouterOS::hotspot()->getUsers();
        $stations = RouterOS::ptmp()->getStations();
        RouterOS::setLimit('budi', '2h');
        return response()->json(['users' => $users, 'stations' => $stations]);
    }
}
```

---

### 4. CodeIgniter (CI3 & CI4)
Gunakan library [`RouterOSLibrary.php`](file:///data/data/com.termux/files/home/project-routeros/src/Integrations/CodeIgniter/RouterOSLibrary.php):
```php
// CodeIgniter 3:
$this->load->library('RouterOSLibrary');
$this->routeroslibrary->setLimit('budi', '2h');

// CodeIgniter 4:
$ros = new \RouterOSFramework\Integrations\CodeIgniter\RouterOSLibrary();
$stations = $ros->ptmp()->getStations();
```

---

### 5. Terminal CLI Console ([`console.php`](file:///data/data/com.termux/files/home/project-routeros/console.php))
```bash
php console.php status
php console.php protocol rest                      # Beralih protokol
php console.php mikhmon:hotspot V15D 3M/2M 15d 0 10000 ntfc Disable hs-pool-22 # Buat profil Hotspot + pool
php console.php mikhmon:pppoe P3 3M/3M 192.168.100.1 isolir 30d                # Buat profil PPPoE + isolir infra
php console.php mikhmon:schedulers install         # Pasang seluruh 6 scheduler resmi Mikhmon
php console.php mikhmon:isolir                     # Pasang pool, profile & scheduler isolir
php console.php mikhmon:setup-all                  # 1-klik bootstrap seluruh ekosistem Mikhmon
php console.php mikhmon:pools                      # Daftar IP pool untuk dropdown
php console.php mikhmon:queues                     # Daftar Simple Queue untuk dropdown
php console.php ptmp stations                      # Pantau sinyal CPE PTMP
php console.php ptmp metrics                       # Health metrics PTMP
php console.php ptmp kick 00:0C:42:AA:BB:CC        # Kick station CPE
php console.php ptmp channel wlan1 5180            # Ganti frekuensi
php console.php setlimit budi 2h 1G                # Ubah limit
php console.php setcomment budi "VIP RT/RW Net"
php console.php setname budi budi_baru
php console.php suspend client01                   # Isolir PPPoE
php console.php unsuspend client01                 # Buka isolir
php console.php create /ip/address address=10.0.0.1/24 interface=ether2
php console.php terminal "/interface wireless print detail"
```

---

## 🌐 REST API JSON Endpoint

File [`api.php`](file:///data/data/com.termux/files/home/project-routeros/api.php) siap dihubungkan ke frontend modern, mobile app, dan sistem billing dengan proteksi API Key dan kompresi Gzip:

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api.php?action=status` | Status resource, identitas router, dan metrik hotspot |
| `POST` | `/api.php?action=batch` | **Batch Multi-Operation**: Eksekusi banyak operasi sekaligus dalam 1 roundtrip |
| `POST` | `/api.php?action=mikhmon_setup_all` | **1-Click Bootstrap**: Pasang seluruh 6 schedulers, pool, profile isolir, dan profil pelanggan |
| `POST` | `/api.php?action=hotspot_setup_mikhmon` | Setup profil Hotspot Mikhmon (MAC cookie, on-login, dropdown pool, auto schedulers) |
| `POST` | `/api.php?action=pppoe_customer_profile` | Setup profil pelanggan PPPoE (`P3`) dengan script on-up & auto-isolir infrastructure |
| `POST` | `/api.php?action=mikhmon_schedulers` | Pasang / perbarui 6 scheduler resmi Mikhmon di RouterOS |
| `POST` | `/api.php?action=ip_ensure_pool` | Buat atau perbarui IP Pool di RouterOS |
| `POST` | `/api.php?action=generate_voucher` | Generate batch voucher standar |
| `POST` | `/api.php?action=generate_bulk_vouchers` | **Mass-Scale 10.000+ Vouchers**: Generate massal dengan batch SQL insert & CLI push |
| `POST` | `/api.php?action=hotspot_create_profile` | Buat profil hotspot dengan on-login script (auto-expiry & auto-MAC lock) |
| `POST` | `/api.php?action=firewall_harden` | Terapkan 1-click baseline keamanan server enterprise |
| `POST` | `/api.php?action=firewall_bruteforce` | Pasang proteksi karantina brute-force port SSH/WinBox/API |
| `POST` | `/api.php?action=firewall_fasttrack` | Aktifkan FastTrack connection acceleration (80% CPU offload) |
| `GET` | `/api.php?action=interfaces` | Daftar seluruh interface server (Ethernet, SFP, Bonding, VRRP, VLAN) |
| `GET` | `/api.php?action=sfp` | Port Fiber Optic SFP/SFP+ 1G/10G/40G |
| `GET` | `/api.php?action=bonding` | Trunk LACP Bonding Link Aggregation |
| `GET` | `/api.php?action=vrrp` | Klaster High-Availability Dual Router VRRP |
| `GET` | `/api.php?action=vlans` | Virtual LAN 802.1Q interfaces |
| `GET` | `/api.php?action=interface_traffic&interface=ether1` | Live telemetry bandwidth interface |
| `GET` | `/api.php?action=database_stats` | Metrik operasional database (driver, voucher stock, user count) |
| `GET` | `/api.php?action=ptmp_stations` | Daftar CPE Station PTMP terhubung & sinyal dBm |
| `GET` | `/api.php?action=ptmp_metrics` | Analisis kualitas link kesehatan PTMP |
| `POST` | `/api.php?action=ptmp_kick` | Kick station CPE tertentu |
| `POST` | `/api.php?action=ptmp_set_channel` | Ganti frekuensi AP Tower PTMP |
| `POST` | `/api.php?action=ptmp_set_protocol` | Ganti mode wireless (nv2, nstreme, 802.11) |
| `GET` | `/api.php?action=users` | Daftar pengguna Hotspot |
| `GET` | `/api.php?action=active` | Sesi aktif dengan konsumsi bandwidth real-time |
| `POST` | `/api.php?action=kick` | Putuskan koneksi user aktif |
| `POST` | `/api.php?action=find_id` | Cari internal .id dari nama item |
| `POST` | `/api.php?action=set_limit` | Ubah batas uptime / bytes akun hotspot |
| `POST` | `/api.php?action=set_comment` | Ubah komentar akun |
| `POST` | `/api.php?action=set_name` | Ganti nama / rename item |
| `POST` | `/api.php?action=pppoe_suspend` | Isolir pelanggan PPPoE |
| `POST` | `/api.php?action=pppoe_unsuspend` | Buka isolir pelanggan PPPoE |
| `POST` | `/api.php?action=pppoe_setup_mikhmon` | Setup profil ISOLIR, NAT redirect, & scheduler auto-isolir |
| `POST` | `/api.php?action=pppoe_reconnect` | Reaktivasi pelanggan pasca bayar (perbarui due date & kick isolir) |
| `POST` | `/api.php?action=pppoe_isolate` | Isolir pelanggan langsung dengan catatan alasan |
| `POST` | `/api.php?action=pppoe_auto_isolate` | Eksekusi pengecekan jatuh tempo dan isolir otomatis |
| `POST` | `/api.php?action=hotspot_setup_mikhmon` | Setup profil Hotspot Mikhmon dengan on-login auto-cleaner |
| `GET`/`POST` | `/api.php?action=router_session` | Baca, set, atau bersihkan sesi runtime terisolasi per-router |
| `GET` | `/api.php?action=router_logs` | Ambil riwayat multi-logging per router ID |
| `POST` | `/api.php?action=bot_broadcast` | Broadcast pesan alert ke multi ChatId Telegram & WhatsApp |
| `POST` | `/api.php?action=create` | Tambah record dinamis pada path apapun |
| `POST` | `/api.php?action=read` | Baca/query fleksibel pada path apapun |
| `POST` | `/api.php?action=generic_set` | Modifikasi properti dinamis pada path apapun |
| `POST` | `/api.php?action=generic_remove` | Hapus item pada path apapun |
| `POST` | `/api.php?action=terminal` | Eksekusi Terminal CLI raw command |

---

## 📋 Cheat Sheet Modul Lengkap

| Modul | Metode Utama |
| :--- | :--- |
| **`create()`** | `create()`, `add()`, `createIfNotExists()`, `batchCreate()`, `ipAddress()`, `hotspotUser()`, `pppoeSecret()`, `queueSimple()`, `firewallRule()`, `natRule()`, `ptmpAccessList()`, `vlan()` |
| **`write()`** | `write()`, `raw()`, `terminal()`, `batch()`, `runScript()`, `executeScriptSource()` |
| **`read()`** | `read()`, `first()`, `monitorTraffic()`, `stats()`, `streamWireless()` |
| **`find()`** | `nameToId()`, `findname_toId()`, `byName()`, `byId()`, `byMac()`, `byIp()`, `where()`, `first()`, `exists()` |
| **`set()`** | `limit()`, `setLimit()`, `comment()`, `setComment()`, `email()`, `setEmail()`, `name()`, `setName()`, `password()`, `profile()`, `property()` |
| **`get()`** | `get()`, `getByName()`, `getById()`, `getValue()`, `count()`, `exists()`, `pluck()` |
| **`print()`** | `print()`, `printColumns()`, `printDetail()`, `printJson()`, `paginate()` |
| **`remove()`** | `remove()`, `byName()`, `byId()`, `where()`, `batch()`, `byComment()`, `byProfile()`, `all()` |
| **`toggle()`** | `enable()`, `disable()`, `toggle()`, `enableBatch()`, `disableBatch()`, `resetCounter()` |
| **`ptmp()`** | `detectHardware()`, `getStations()`, `getStationQualityMetrics()`, `getAccessList()`, `addAccessList()`, `removeAccessList()`, `getConnectList()`, `addConnectList()`, `kickStation()`, `setProtocol()`, `setNv2Config()`, `setChannel()`, `setTxPower()`, `scanFrequencies()` |
| **`hotspot()`** | `getUsers()`, `addUserDetailed()`, `setUser()`, `setLimit()`, `setComment()`, `setEmail()`, `setName()`, `findNameToId()`, `createVoucherProfile()`, `generateOnLoginScript()`, `setupMikhmonProfile()`, `installExpiryCleanupScheduler()`, `enableUser()`, `disableUser()`, `getUserTraffic()`, `getTopDownloaders()`, `getTopActiveTime()`, `extendUserTime()`, `extendUserData()`, `lockUserToMac()`, `unlockUserMac()`, `getHotspotDhcpLeases()`, `getActiveTraffic()`, `disconnectUser()`, `bypassDevice()`, `blockDevice()`, `addWalledGardenDomain()`, `removeExpiredUsers()`, `syncToDatabase()` |
| **`voucher()`** | `generate()`, `generateFastBulk()`, `generateRscScript()`, `generateAndSave()`, `generateQrCodeUrl()`, `getFromDb()`, `markUsed()`, `deleteBatch()`, `renderPrintCards()`, `renderThermalReceipt()`, `getSalesReport()`, `getBatchSummary()`, `exportToCsv()`, `importFromCsv()` |
| **`pppoe()`** | `getUsers()`, `addUser()`, `setUser()`, `setComment()`, `setPassword()`, `setProfile()`, `setName()`, `findNameToId()`, `enableUser()`, `disableUser()`, `removeUser()`, `getActive()`, `getUserTraffic()`, `getTopActiveTime()`, `disconnectUser()`, `suspendCustomer()`, `unsuspendCustomer()`, `isolateCustomer()`, `reconnectCustomer()`, `bindCallerId()`, `unbindCallerId()`, `assignStaticIp()`, `setPppoeDueDate()`, `getOverdueCustomers()`, `autoIsolateOverdue()`, `ensureIsolateProfile()`, `setupMikhmonIsolirProfile()`, `installPppoeIsolirScheduler()`, `syncToDatabase()` |
| **`session()`** | `getSessionId()`, `getToken()`, `setToken()`, `get()`, `set()`, `has()`, `remove()`, `all()`, `touch()`, `isValid()`, `clear()`, `destroy()` |
| **`bot()`** | `broadcast()`, `sendTelegram()`, `sendWhatsApp()`, `addTelegramChatId()`, `addWhatsAppRecipient()`, `notifyRouterAlert()` |
| **`firewall()`** | `getFilterRules()`, `addFilterRule()`, `getNatRules()`, `addMasquerade()`, `addAddressList()`, `enableFastTrack()`, `dropInvalid()`, `protectBruteForce()`, `protectPortScanners()`, `hardenServerBaseline()` |
| **`queue()`** | `getAll()`, `add()`, `limitBandwidth()`, `remove()` |
| **`interface()`** | `getAll()`, `getEthernet()`, `getSfp()`, `getBridges()`, `getVlans()`, `getBonding()`, `getVrrp()`, `getWireGuard()`, `getEoip()`, `getGre()`, `getVxlan()`, `monitorTraffic()`, `enable()`, `disable()`, `setComment()` |
| **`system()`** | `getResources()`, `getIdentity()`, `setIdentity()`, `getClock()`, `getLogs()`, `ping()`, `reboot()`, `getVersion()`, `getMajorVersion()`, `isV7()`, `isV6()` |
| **`ip()`** | `getAddresses()`, `addAddress()`, `getPools()`, `getDhcpLeases()`, `makeDhcpLeaseStatic()`, `getArpTable()` |
| **`backup()`** | `saveBackup()`, `exportConfig()`, `getFiles()`, `removeFile()` |
| **`tool()`** | `wakeOnLan()`, `sendEmail()`, `sendSms()`, `traceroute()`, `ipScan()`, `bandwidthTest()` |
| **`vpn()`** | `getWireguardPeers()`, `addWireguardPeer()`, `removeWireguardPeer()`, `getL2tpServer()` |
| **`billing()`** | `handleWebhook()`, `generateVoucherForPayment()` |
| **`dnsFilter()`** | `blockDomain()`, `unblockDomain()`, `flushCache()`, `importHostsBlocklist()` |
| **`bot()`** | `handle($message, $sender)` |
| **`db()`** | `insert()`, `insertBatch()`, `transaction()`, `beginTransaction()`, `commit()`, `rollBack()`, `stats()`, `update()`, `delete()`, `select()`, `find()`, `count()`, `log()`, `query()`, `fetchAll()` |
| **`json()`** | `all()`, `get()`, `set()`, `push()`, `pushBatch()`, `find()`, `where()`, `deleteWhere()`, `clear()` |
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
