<?php
require 'vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\Helper;

// Initialize RouterOS Framework with config.php (includes MikroTik & SQL Database)
$ros = RouterOS::withConfig('config.php');

$isConnected = $ros->isConnected();
$connectionError = $ros->getConnectionError();
$db = $ros->db();
$hasDb = $ros->hasDatabase();

$notification = null;
$generatedVouchers = null;

// Handle Form Submissions (Action Handlers)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate_vouchers') {
        $count = max(1, min(100, (int)($_POST['count'] ?? 5)));
        $prefix = trim($_POST['prefix'] ?? 'VC-');
        $profile = trim($_POST['profile'] ?? 'default');
        $limitUptime = trim($_POST['limit_uptime'] ?? '');
        $limitBytes = trim($_POST['limit_bytes'] ?? '');
        $userMode = $_POST['user_mode'] ?? 'user_password';
        $charType = $_POST['char_type'] ?? 'alphanumeric';
        $price = (float)($_POST['price'] ?? 0);

        $options = [
            'prefix'            => $prefix,
            'profile'           => $profile,
            'user_mode'         => $userMode,
            'characters'        => $charType,
            'price'             => $price,
            'batch_tag'         => 'web-' . date('Ymd-His'),
        ];
        if (!empty($limitUptime)) $options['limit-uptime'] = $limitUptime;
        if (!empty($limitBytes)) $options['limit-bytes-total'] = $limitBytes;

        // Generate and save to both RouterOS and SQL DB
        $genResult = $ros->voucher()->generateAndSave($count, $options);
        if (!empty($genResult['vouchers'])) {
            $generatedVouchers = $genResult['vouchers'];
            $dbMsg = $hasDb ? ' & disimpan ke Database SQL' : '';
            $notification = [
                'type' => 'success',
                'msg'  => "Berhasil membuat {$genResult['generated']} voucher baru ke MikroTik{$dbMsg}! (Batch: {$genResult['batch_tag']})"
            ];
        } else {
            $notification = [
                'type' => 'error',
                'msg'  => 'Gagal membuat voucher. Periksa koneksi router: ' . ($connectionError ?? 'Router offline')
            ];
        }
    } elseif ($action === 'sync_to_db' && $isConnected && $hasDb) {
        $syncRes = $ros->hotspot()->syncToDatabase();
        if (!isset($syncRes['error'])) {
            $notification = [
                'type' => 'success',
                'msg'  => "Sinkronisasi berhasil! {$syncRes['synced']} user MikroTik berhasil disinkronkan ke Database SQL."
            ];
        } else {
            $notification = ['type' => 'error', 'msg' => 'Gagal sinkronisasi: ' . $syncRes['error']];
        }
    } elseif ($action === 'disconnect_user' && $isConnected) {
        $targetUser = trim($_POST['username'] ?? '');
        if (!empty($targetUser)) {
            $res = $ros->hotspot()->disconnectUser($targetUser);
            $notification = [
                'type' => 'success',
                'msg'  => "Pengguna '{$targetUser}' telah diputuskan dari Hotspot ({$res['disconnected']} sesi ditutup)."
            ];
        }
    } elseif ($action === 'bypass_device' && $isConnected) {
        $mac = trim($_POST['mac_address'] ?? '');
        $comment = trim($_POST['comment'] ?? 'Bypassed from Web');
        if (!empty($mac)) {
            $res = $ros->hotspot()->bypassDevice($mac, null, $comment);
            if (!isset($res['error'])) {
                $notification = [
                    'type' => 'success',
                    'msg'  => "Perangkat MAC '{$mac}' berhasil di-bypass tanpa perlu login!"
                ];
            } else {
                $notification = ['type' => 'error', 'msg' => 'Gagal bypass: ' . $res['error']];
            }
        }
    } elseif ($action === 'remove_expired' && $isConnected) {
        $res = $ros->hotspot()->removeExpiredUsers();
        $notification = [
            'type' => 'success',
            'msg'  => "Pembersihan selesai: {$res['removed']} pengguna expired dihapus."
        ];
    } elseif ($action === 'delete_voucher_batch' && $hasDb) {
        $batchTag = trim($_POST['batch_tag'] ?? '');
        if (!empty($batchTag)) {
            $delRes = $ros->voucher()->deleteBatch($batchTag);
            $notification = [
                'type' => 'success',
                'msg'  => "Batch '{$batchTag}' berhasil dihapus ({$delRes['routeros_removed']} dari MikroTik, {$delRes['database_removed']} dari Database)."
            ];
        }
    }
}

// Data fetching
$summary = $isConnected ? $ros->hotspot()->getSummary() : [];
$activeTraffic = $isConnected ? $ros->hotspot()->getActiveTraffic() : [];
$userProfiles = $isConnected ? $ros->hotspot()->getUserProfiles() : [];
$systemResources = $isConnected ? $ros->system()->getResources() : [];
$routerIdentity = $isConnected ? $ros->system()->getIdentity() : [];
$hosts = $isConnected ? $ros->hotspot()->getHosts() : [];

// Fetch vouchers from SQL database
$vouchersInDb = $hasDb ? $ros->voucher()->getFromDb(['limit' => 20]) : [];
$totalVouchersInDb = $hasDb ? $db->count('vouchers') : 0;

$identityName = (!empty($routerIdentity) && isset($routerIdentity[0]['name'])) ? $routerIdentity[0]['name'] : 'MikroTik Router';
$sysCpu = (!empty($systemResources) && isset($systemResources[0]['cpu-load'])) ? $systemResources[0]['cpu-load'] . '%' : '-';
$sysUptime = (!empty($systemResources) && isset($systemResources[0]['uptime'])) ? $systemResources[0]['uptime'] : '-';
$sysBoard = (!empty($systemResources) && isset($systemResources[0]['board-name'])) ? $systemResources[0]['board-name'] : '-';

$configArr = $ros->connection()->getConfig();
$host = $configArr['host'];
$port = $configArr['port'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikroTik RouterOS API Framework</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="app-header">
        <div class="header-container">
            <div class="header-title">
                <h1>⚡ RouterOS PHP Framework (Full Version)</h1>
                <p class="subtitle"><?= htmlspecialchars($identityName) ?> &bull; <?= htmlspecialchars($sysBoard) ?></p>
            </div>
            <div class="header-status">
                <?php if ($isConnected): ?>
                    <span class="badge badge-online">● Router: Terhubung (<?= htmlspecialchars($host) ?>:<?= $port ?>)</span>
                    <span class="badge badge-info">CPU: <?= htmlspecialchars($sysCpu) ?> | Uptime: <?= htmlspecialchars($sysUptime) ?></span>
                <?php else: ?>
                    <span class="badge badge-offline">● Router: Terputus</span>
                <?php endif; ?>

                <?php if ($hasDb): ?>
                    <span class="badge badge-online">● SQL Database: Aktif</span>
                <?php else: ?>
                    <span class="badge badge-warning">● SQL Database: Nonaktif</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-container">
        <?php if (!$isConnected): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Tidak dapat terhubung langsung ke Router MikroTik!</strong><br>
                Fitur manual router membutuhkan perangkat MikroTik aktif. Namun fitur SQL Database lokal tetap berjalan normal.<br>
                <em>Pesan error: <?= htmlspecialchars($connectionError ?? 'Koneksi ditolak / Host offline') ?></em>
            </div>
        <?php endif; ?>

        <?php if (!empty($notification)): ?>
            <div class="alert alert-<?= $notification['type'] === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars($notification['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- Printable Vouchers Preview if newly generated -->
        <?php if (!empty($generatedVouchers)): ?>
            <section class="card voucher-preview-card">
                <div class="card-header">
                    <h2>🎫 Hasil Generate Voucher Siap Cetak</h2>
                    <button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Cetak Sekarang</button>
                </div>
                <div class="card-body">
                    <?= $ros->voucher()->renderPrintCards($generatedVouchers, [
                        'title'    => $identityName . ' HOTSPOT',
                        'dns_name' => 'http://hotspot.local',
                    ]) ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Metric Summary Cards -->
        <div class="metrics-grid">
            <div class="metric-card metric-primary">
                <div class="metric-icon">👥</div>
                <div class="metric-data">
                    <span class="metric-value"><?= $summary['total_users'] ?? 0 ?></span>
                    <span class="metric-label">Total User RouterOS</span>
                </div>
            </div>
            <div class="metric-card metric-success">
                <div class="metric-icon">🟢</div>
                <div class="metric-data">
                    <span class="metric-value"><?= $summary['active_users'] ?? 0 ?></span>
                    <span class="metric-label">Sesi Online Aktif</span>
                </div>
            </div>
            <div class="metric-card metric-info">
                <div class="metric-icon">🗄️</div>
                <div class="metric-data">
                    <span class="metric-value"><?= $totalVouchersInDb ?></span>
                    <span class="metric-label">Voucher di SQL Database</span>
                </div>
            </div>
            <div class="metric-card metric-warning">
                <div class="metric-icon">🔓</div>
                <div class="metric-data">
                    <span class="metric-value"><?= $summary['bypassed_bindings'] ?? 0 ?></span>
                    <span class="metric-label">Perangkat Bypass (VIP)</span>
                </div>
            </div>
        </div>

        <!-- Section: Active Hotspot Users -->
        <section class="card">
            <div class="card-header">
                <h2>🌐 Pengguna Hotspot Aktif (Live Sessions)</h2>
                <div>
                    <span class="badge badge-pill"><?= count($activeTraffic) ?> Online</span>
                    <?php if ($isConnected && $hasDb): ?>
                        <form method="POST" style="display:inline; margin-left: 10px;">
                            <input type="hidden" name="action" value="sync_to_db">
                            <button type="submit" class="btn btn-sm btn-info">🔄 Sync User ke SQL DB</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>IP Address</th>
                            <th>MAC Address</th>
                            <th>Uptime</th>
                            <th>Download (RX)</th>
                            <th>Upload (TX)</th>
                            <th>Total Transfer</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activeTraffic) || isset($activeTraffic['error'])): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada pengguna Hotspot yang sedang login.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeTraffic as $session): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($session['user']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($session['address']) ?></code></td>
                                    <td><small><?= htmlspecialchars($session['mac_address']) ?></small></td>
                                    <td><?= htmlspecialchars($session['uptime']) ?></td>
                                    <td><span class="text-rx">↓ <?= htmlspecialchars($session['bytes_in_human']) ?></span></td>
                                    <td><span class="text-tx">↑ <?= htmlspecialchars($session['bytes_out_human']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($session['total_human']) ?></strong></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Putuskan sesi user <?= htmlspecialchars($session['user']) ?>?');">
                                            <input type="hidden" name="action" value="disconnect_user">
                                            <input type="hidden" name="username" value="<?= htmlspecialchars($session['user']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Kick</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Two Column Actions: Generate Vouchers & SQL Database Vouchers -->
        <div class="two-column-grid">
            <!-- Generator Voucher Card -->
            <section class="card">
                <div class="card-header">
                    <h2>🎫 Batch Voucher Generator (Sync SQL)</h2>
                </div>
                <div class="card-body">
                    <form method="POST" class="action-form">
                        <input type="hidden" name="action" value="generate_vouchers">
                        
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="count">Jumlah Voucher</label>
                                <input type="number" id="count" name="count" value="5" min="1" max="100" required class="form-control">
                            </div>
                            <div class="form-group col-6">
                                <label for="prefix">Awalan (Prefix)</label>
                                <input type="text" id="prefix" name="prefix" value="VC-" placeholder="Contoh: VC-" class="form-control">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="profile">Hotspot Profile</label>
                                <select id="profile" name="profile" class="form-control">
                                    <option value="default">default</option>
                                    <?php if (is_array($userProfiles) && !isset($userProfiles['error'])): ?>
                                        <?php foreach ($userProfiles as $p): ?>
                                            <?php if (isset($p['name']) && $p['name'] !== 'default'): ?>
                                                <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group col-6">
                                <label for="user_mode">Mode Voucher</label>
                                <select id="user_mode" name="user_mode" class="form-control">
                                    <option value="user_password">Username = Password</option>
                                    <option value="separate">Password Berbeda</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="limit_uptime">Batas Waktu (Uptime)</label>
                                <input type="text" id="limit_uptime" name="limit_uptime" placeholder="1h, 3h, 1d" class="form-control">
                            </div>
                            <div class="form-group col-6">
                                <label for="limit_bytes">Batas Kuota Total</label>
                                <input type="text" id="limit_bytes" name="limit_bytes" placeholder="500M, 1G" class="form-control">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-6">
                                <label for="char_type">Karakter Kode</label>
                                <select id="char_type" name="char_type" class="form-control">
                                    <option value="alphanumeric">Alphanumeric (angka + huruf)</option>
                                    <option value="numeric">Angka Saja (PIN)</option>
                                    <option value="alpha">Huruf Saja</option>
                                </select>
                            </div>
                            <div class="form-group col-6">
                                <label for="price">Harga Jual (Rp)</label>
                                <input type="number" id="price" name="price" value="5000" step="500" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">🚀 Buat Voucher &amp; Simpan ke SQL DB</button>
                    </form>
                </div>
            </section>

            <!-- Device Bypass & Tools -->
            <div class="stack-cards">
                <section class="card">
                    <div class="card-header">
                        <h2>🔓 Bypass Perangkat (Tanpa Login)</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="action-form">
                            <input type="hidden" name="action" value="bypass_device">
                            <div class="form-group">
                                <label for="mac_address">MAC Address Perangkat</label>
                                <input type="text" id="mac_address" name="mac_address" placeholder="Contoh: 00:11:22:33:44:55" required class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="comment">Keterangan (Nama Perangkat)</label>
                                <input type="text" id="comment" name="comment" placeholder="Smart TV / CCTV / Laptop Pimpinan" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-warning btn-block">Tambahkan ke Bypass</button>
                        </form>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header">
                        <h2>🧹 Pembersihan Otomatis</h2>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                            Bersihkan semua akun pengguna voucher di MikroTik yang batas waktu atau kuotanya sudah habis untuk menghemat memori router.
                        </p>
                        <form method="POST" onsubmit="return confirm('Hapus semua user voucher yang sudah expired dari router?');">
                            <input type="hidden" name="action" value="remove_expired">
                            <button type="submit" class="btn btn-secondary btn-block">Hapus Semua User Expired</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>

        <!-- Section: Vouchers stored in SQL Database -->
        <?php if ($hasDb): ?>
            <section class="card">
                <div class="card-header">
                    <h2>🗄️ Riwayat Voucher Tersimpan di Database SQL</h2>
                    <span class="badge badge-pill"><?= $totalVouchersInDb ?> Total Data</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Profile</th>
                                <th>Batas Waktu</th>
                                <th>Batas Kuota</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Batch</th>
                                <th>Waktu Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vouchersInDb)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada voucher yang disimpan di database SQL. Buat voucher di atas untuk menyimpannya secara otomatis.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vouchersInDb as $v): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($v['username']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($v['password']) ?></code></td>
                                        <td><?= htmlspecialchars($v['profile']) ?></td>
                                        <td><?= htmlspecialchars($v['limit_uptime'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($v['limit_bytes_total'] ?? '-') ?></td>
                                        <td>Rp <?= number_format((float)($v['price'] ?? 0), 0, ',', '.') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $v['status'] === 'active' ? 'online' : 'offline' ?>">
                                                <?= htmlspecialchars($v['status']) ?>
                                            </span>
                                        </td>
                                        <td><small><?= htmlspecialchars($v['batch_tag'] ?? '-') ?></small></td>
                                        <td><small><?= htmlspecialchars($v['created_at']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="app-footer">
        <p>Project RouterOS Framework &bull; Full-Version PHP API with Manual &amp; SQL Database Engine</p>
    </footer>

    <script src="scripts.js"></script>
</body>
</html>
