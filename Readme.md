<div align="center" id="top">
  <h1>⚡ Project RouterOS PHP Framework (Full Version)</h1>
  <p><b>Lightweight, Multi-Protocol &amp; Enterprise-Ready PHP API Framework for MikroTik RouterOS</b><br>
  Inspired by <a href="https://github.com/comitIdn/RouterOS-API">comitIdn/RouterOS-API</a> &bull; Full Support for <b>Multi-Protocol Transports (API, REST, SSH, Telnet)</b>, <b>Multi-PTMP Wireless</b>, <b>Full CRUD Actions</b>, <b>SQL Database</b>, <b>JSON Storage</b>, <b>Web Apps</b>, <b>Python SDK</b>, <b>JavaScript SDK</b>, <b>Chat Bots</b>, and <b>Terminal CLI</b></p>
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
  <a href="#-multi-ptmp-point-to-multipoint-wireless-management">PTMP Wireless</a> &nbsp;|&nbsp;
  <a href="#-dukungan-multi-platform--bahasa-php-python-js-cli-json">Multi-Bahasa</a> &nbsp;|&nbsp;
  <a href="#-rest-api-json-endpoint">REST API</a> &nbsp;|&nbsp;
  <a href="#-terminal-cli-console-tool">Terminal CLI</a> &nbsp;|&nbsp;
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
3. **Multi-PTMP Wireless Management (Untuk Tower & Base Station WISP / RT-RW Net)**:
   - Mendukung segala jenis perangkat MikroTik: **802.11a/b/g/n/ac**, **MikroTik NV2 TDMA**, **Nstreme / Dual Nstreme**, **60GHz (w60g)**, dan **WiFiWave2 / WiFi 6**.
   - Pantau seluruh CPE Station yang terhubung: Signal dBm (TX/RX), CCQ %, SNR, Throughput TX/RX, Jarak (km), Versi RouterOS, dan Status Kesehatan Link (*Health Index*).
   - Manajemen Access-List (Whitelist/Blacklist MAC dengan batas toleransi sinyal), Connect-List, Kick Station, Ganti Frekuensi, Channel Width, dan Pengaturan NV2.
4. **Dukungan Multi-Bahasa & Lingkungan Pemrograman**:
   - **PHP**: Native / Manual, Laravel Facade & ServiceProvider, CodeIgniter 3 & 4.
   - **Python SDK (`routeros.py`)**: Standalone library Python 3 tanpa dependensi (zero-dependency).
   - **JavaScript SDK (`assets/js/routeros.js`)**: Universal UMD SDK untuk Browser, Vue, React, Svelte, dan Node.js.
   - **RESTful JSON API (`api.php`)**: Endpoint siap pakai dengan CORS untuk aplikasi Mobile & Web.
   - **Terminal CLI Console (`console.php`)**: Manajemen interaktif langsung dari terminal Linux/Termux.
5. **Dua Jenis Penyimpanan Database (SQL PDO & JSON Flat-File)**:
   - **SQLite** tanpa instalasi server database (*zero-config*) dan dukungan **MySQL / MariaDB / PostgreSQL**.
   - **JSON Flat-File Storage**: Simpan konfigurasi, voucher, dan log langsung sebagai file JSON.

---

## 🏗️ Struktur Arsitektur

```text
project-routeros/
├── config.example.php          # Template Konfigurasi Router & Database
├── config.php                  # Konfigurasi Lokal (Host, User, Pass, DB, Protocol)
├── composer.json               # Dependensi & Autoloading PSR-4 + Laravel Auto-Discovery
├── example.php                 # Master Contoh Implementasi Bersih & Profesional
├── routeros.py                 # Universal Python 3 SDK & Standalone CLI
├── routeros.js                 # Universal Master JavaScript SDK (Node.js & Browser)
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
│   ├── 06_multi_platform.php   # Panduan integrasi Laravel, CodeIgniter, JS SDK, CLI
│   ├── 07_ptmp_and_protocols.php # Multi-Protocol Transports & Multi-PTMP Wireless
│   └── 08_javascript_master.js # Master JavaScript Multi-Router Concurrent Pool & Debugger
├── assets/
│   └── js/
│       └── routeros.js         # Client-side JavaScript SDK Universal (Vanilla, Vue, React, Node)
├── database/
│   ├── schema.sql              # Skema SQL (routers, vouchers, users, logs)
│   ├── routeros.sqlite         # Database SQLite lokal (dibuat otomatis)
│   ├── json/                   # Direktori penyimpanan database flat JSON
│   └── cache/                  # Direktori cache response MikroTik (TTL)
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
│   │   ├── Database.php        # Lightweight SQL PDO Engine (SQLite & MySQL)
│   │   ├── JsonStorage.php     # Engine Database File JSON Flat-Store
│   │   ├── Cache.php           # High-Performance TTL Caching Layer
│   │   ├── MultiRouter.php     # Enterprise Multi-Router Manager
│   │   ├── Response.php        # Standardized JSON Response Formatter
│   │   └── Helper.php          # Utilitas format bytes, validasi IP/MAC, PIN acak
│   └── Modules/
│       ├── Ptmp.php            # Multi-PTMP Wireless: NV2, Nstreme, 60GHz, Sinyal, CCQ, Access-List
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
| **`Remover`** | `$ros->remove()` / `$ros->remover()` | `remove()`, `byName()`, `byId()`, `where()`, `byComment()`, `byProfile()`, `all()` |
| **`Toggler`** | `$ros->toggle()` / `$ros->toggler()` | `enable()`, `disable()`, `toggle()`, `resetCounter()` |

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

File [`api.php`](file:///data/data/com.termux/files/home/project-routeros/api.php) siap dihubungkan ke frontend modern:

| Method | Endpoint | Keterangan |
| :--- | :--- | :--- |
| `GET` | `/api.php?action=status` | Status resource, identitas router, dan metrik hotspot |
| `GET` | `/api.php?action=ptmp_stations` | Daftar CPE Station PTMP terhubung & sinyal dBm |
| `GET` | `/api.php?action=ptmp_metrics` | Analisis kualitas link kesehatan PTMP |
| `POST` | `/api.php?action=ptmp_kick` | Kick station CPE tertentu |
| `POST` | `/api.php?action=ptmp_set_channel` | Ganti frekuensi AP Tower PTMP |
| `POST` | `/api.php?action=ptmp_set_protocol` | Ganti mode wireless (nv2, nstreme, 802.11) |
| `GET` | `/api.php?action=users` | Daftar pengguna Hotspot |
| `GET` | `/api.php?action=active` | Sesi aktif dengan konsumsi bandwidth real-time |
| `POST` | `/api.php?action=generate_voucher` | Generate batch voucher baru |
| `POST` | `/api.php?action=kick` | Putuskan koneksi user aktif |
| `POST` | `/api.php?action=find_id` | Cari internal .id dari nama item |
| `POST` | `/api.php?action=set_limit` | Ubah batas uptime / bytes akun hotspot |
| `POST` | `/api.php?action=set_comment` | Ubah komentar akun |
| `POST` | `/api.php?action=set_name` | Ganti nama / rename item |
| `POST` | `/api.php?action=pppoe_suspend` | Isolir pelanggan PPPoE |
| `POST` | `/api.php?action=pppoe_unsuspend` | Buka isolir pelanggan PPPoE |
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
| **`remove()`** | `remove()`, `byName()`, `byId()`, `where()`, `byComment()`, `byProfile()`, `all()` |
| **`toggle()`** | `enable()`, `disable()`, `toggle()`, `resetCounter()` |
| **`ptmp()`** | `detectHardware()`, `getStations()`, `getStationQualityMetrics()`, `getAccessList()`, `addAccessList()`, `removeAccessList()`, `getConnectList()`, `addConnectList()`, `kickStation()`, `setProtocol()`, `setNv2Config()`, `setChannel()`, `setTxPower()`, `scanFrequencies()` |
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
