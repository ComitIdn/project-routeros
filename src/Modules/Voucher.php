<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Connection;
use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\Helper;

/**
 * Class Voucher
 * Voucher generation, management, SQL persistence, and printable card rendering engine.
 */
class Voucher
{
    private Connection $conn;
    private ?Database $db;

    public function __construct(Connection $conn, ?Database $db = null)
    {
        $this->conn = $conn;
        $this->db = $db;
    }

    /**
     * Generate vouchers and push to MikroTik Hotspot (Manual mode).
     *
     * @param int $count
     * @param array $options
     * @return array
     */
    public function generate(int $count, array $options = []): array
    {
        $length = (int)($options['length'] ?? 6);
        $charType = $options['characters'] ?? 'alphanumeric';
        $prefix = $options['prefix'] ?? '';
        $userMode = $options['user_mode'] ?? 'user_password';
        $profile = $options['profile'] ?? 'default';
        $limitUptime = $options['limit-uptime'] ?? ($options['limit_uptime'] ?? '');
        $limitBytes = $options['limit-bytes-total'] ?? ($options['limit_bytes'] ?? '');
        $server = $options['server'] ?? 'all';
        $batchTag = $options['comment'] ?? ($options['batch_tag'] ?? 'batch-' . date('Ymd-His'));
        $price = (float)($options['price'] ?? 0);

        $vouchers = [];
        $errors = [];
        $successCount = 0;

        for ($i = 0; $i < $count; $i++) {
            $code = Helper::randomString($length, $charType);
            $username = $prefix . $code;
            $password = ($userMode === 'user_password') ? $username : Helper::randomString($length, $charType);

            $params = [
                'name'     => $username,
                'password' => $password,
                'profile'  => $profile,
                'comment'  => $batchTag,
            ];
            if (!empty($server) && $server !== 'all') $params['server'] = $server;
            if (!empty($limitUptime)) $params['limit-uptime'] = $limitUptime;
            if (!empty($limitBytes)) $params['limit-bytes-total'] = $limitBytes;

            $res = $this->conn->write('/ip/hotspot/user/add', $params);

            if (isset($res['error'])) {
                $errors[] = ['username' => $username, 'error' => $res['error']];
            } else {
                $successCount++;
                $vouchers[] = [
                    'username'          => $username,
                    'password'          => $password,
                    'profile'           => $profile,
                    'limit_uptime'      => $limitUptime,
                    'limit_bytes_total' => $limitBytes,
                    'price'             => $price,
                    'status'            => 'active',
                    'batch_tag'         => $batchTag,
                    'comment'           => $batchTag,
                    'created_at'        => date('Y-m-d H:i:s'),
                ];
            }
        }

        return [
            'batch_tag' => $batchTag,
            'requested' => $count,
            'generated' => $successCount,
            'vouchers'  => $vouchers,
            'errors'    => $errors,
        ];
    }

    /**
     * Generate vouchers, push to MikroTik RouterOS, AND save to SQL Database in one call.
     *
     * @param int $count
     * @param array $options
     * @param int|null $routerId
     * @return array
     */
    public function generateAndSave(int $count, array $options = [], ?int $routerId = null): array
    {
        $batch = $this->generate($count, $options);

        if ($this->db && !empty($batch['vouchers'])) {
            foreach ($batch['vouchers'] as $v) {
                $this->db->insert('vouchers', [
                    'router_id'         => $routerId,
                    'username'          => $v['username'],
                    'password'          => $v['password'],
                    'profile'           => $v['profile'],
                    'limit_uptime'      => $v['limit_uptime'],
                    'limit_bytes_total' => $v['limit_bytes_total'],
                    'price'             => $v['price'],
                    'status'            => 'active',
                    'batch_tag'         => $v['batch_tag'],
                    'comment'           => $v['comment'],
                    'created_at'        => $v['created_at'],
                ]);
            }
            $this->db->log('Voucher', 'Batch Generate', $batch['batch_tag'], "Generated {$batch['generated']} vouchers", 'success', $routerId);
        }

        return $batch;
    }

    /**
     * Fetch vouchers from SQL Database.
     *
     * @param array $filters [
     *    'status' => 'active'|'used'|'expired',
     *    'batch_tag' => string,
     *    'profile' => string,
     *    'limit' => int
     * ]
     * @return array
     */
    public function getFromDb(array $filters = []): array
    {
        if (!$this->db) {
            return [];
        }

        $whereParts = [];
        $params = [];

        if (!empty($filters['status'])) {
            $whereParts[] = "status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['batch_tag'])) {
            $whereParts[] = "batch_tag = :batch_tag";
            $params['batch_tag'] = $filters['batch_tag'];
        }
        if (!empty($filters['profile'])) {
            $whereParts[] = "profile = :profile";
            $params['profile'] = $filters['profile'];
        }

        $where = implode(' AND ', $whereParts);
        $limit = (int)($filters['limit'] ?? 100);

        return $this->db->select('vouchers', $where, $params, 'id DESC', $limit);
    }

    /**
     * Mark a voucher as used in SQL Database.
     *
     * @param string $username
     * @return bool
     */
    public function markUsed(string $username): bool
    {
        if (!$this->db) return false;
        return (bool)$this->db->update('vouchers', [
            'status' => 'used',
        ], 'username = :u', ['u' => $username]);
    }

    /**
     * Delete voucher batch from MikroTik and SQL Database.
     *
     * @param string $batchTag
     * @return array
     */
    public function deleteBatch(string $batchTag): array
    {
        // Remove from MikroTik
        $users = $this->conn->read('/ip/hotspot/user/print', ['comment' => $batchTag]);
        $rosRemoved = 0;
        if (!empty($users) && is_array($users) && !isset($users['error'])) {
            foreach ($users as $u) {
                if (isset($u['.id'])) {
                    $this->conn->remove('/ip/hotspot/user/remove', $u['.id']);
                    $rosRemoved++;
                }
            }
        }

        // Remove from SQL Database
        $dbRemoved = 0;
        if ($this->db) {
            $dbRemoved = $this->db->delete('vouchers', 'batch_tag = :b', ['b' => $batchTag]);
            $this->db->log('Voucher', 'Delete Batch', $batchTag, "Removed {$rosRemoved} from ROS, {$dbRemoved} from DB", 'success');
        }

        return [
            'batch_tag'        => $batchTag,
            'routeros_removed' => $rosRemoved,
            'database_removed' => $dbRemoved,
        ];
    }

    /**
     * Generate QR Code image URL for instant camera-scan Wi-Fi login.
     *
     * @param string $username
     * @param string $password
     * @param string $loginUrl (e.g. 'http://hotspot.net/login')
     * @param int $size Pixels width/height
     * @return string
     */
    public function generateQrCodeUrl(string $username, string $password, string $loginUrl = 'http://hotspot.net/login', int $size = 120): string
    {
        $target = rtrim($loginUrl, '/') . "?username=" . urlencode($username) . "&password=" . urlencode($password);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($target);
    }

    /**
     * Get financial sales & voucher inventory report.
     *
     * @param array $filters ['batch_tag' => string, 'profile' => string]
     * @return array
     */
    public function getSalesReport(array $filters = []): array
    {
        if (!$this->db) {
            return ['error' => 'Database layer is required for sales reports'];
        }

        $allVouchers = $this->getFromDb($filters);
        $totalCount = count($allVouchers);
        $usedCount = 0;
        $activeCount = 0;
        $totalRevenue = 0.0;
        $realizedRevenue = 0.0;

        foreach ($allVouchers as $v) {
            $price = (float)($v['price'] ?? 0);
            $totalRevenue += $price;

            if (($v['status'] ?? '') === 'used') {
                $usedCount++;
                $realizedRevenue += $price;
            } elseif (($v['status'] ?? '') === 'active') {
                $activeCount++;
            }
        }

        return [
            'total_vouchers'    => $totalCount,
            'active_vouchers'   => $activeCount,
            'used_vouchers'     => $usedCount,
            'total_potential'   => $totalRevenue,
            'total_realized'    => $realizedRevenue,
            'realized_human'    => 'Rp ' . number_format($realizedRevenue, 0, ',', '.'),
            'potential_human'   => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
        ];
    }

    /**
     * Get summary performance of a specific voucher batch.
     *
     * @param string $batchTag
     * @return array
     */
    public function getBatchSummary(string $batchTag): array
    {
        $report = $this->getSalesReport(['batch_tag' => $batchTag]);
        $report['batch_tag'] = $batchTag;
        return $report;
    }

    /**
     * Export vouchers to CSV format (Excel compatible).
     *
     * @param string $batchTag Optional batch filter
     * @param string|null $filePath Optional path to save CSV file directly
     * @return string CSV text content
     */
    public function exportToCsv(string $batchTag = '', ?string $filePath = null): string
    {
        $filters = $batchTag ? ['batch_tag' => $batchTag] : [];
        $vouchers = $this->getFromDb($filters);

        $output = "Username,Password,Profile,Limit Uptime,Limit Bytes,Price,Status,Batch Tag,Created At\n";
        foreach ($vouchers as $v) {
            $output .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",%s,\"%s\",\"%s\",\"%s\"\n",
                $v['username'] ?? '',
                $v['password'] ?? '',
                $v['profile'] ?? '',
                $v['limit_uptime'] ?? '',
                $v['limit_bytes_total'] ?? '',
                (float)($v['price'] ?? 0),
                $v['status'] ?? 'active',
                $v['batch_tag'] ?? '',
                $v['created_at'] ?? ''
            );
        }

        if ($filePath) {
            file_put_contents($filePath, $output);
        }

        return $output;
    }

    /**
     * Import existing voucher list from CSV content or file.
     *
     * @param string $csvContentOrPath
     * @param string $defaultProfile
     * @return array
     */
    public function importFromCsv(string $csvContentOrPath, string $defaultProfile = 'default'): array
    {
        $content = file_exists($csvContentOrPath) ? file_get_contents($csvContentOrPath) : $csvContentOrPath;
        $lines = explode("\n", trim($content));
        $imported = 0;
        $errors = [];

        foreach ($lines as $idx => $line) {
            if ($idx === 0 && stripos($line, 'username') !== false) {
                continue; // Skip CSV header
            }

            $cols = str_getcsv($line);
            if (empty($cols[0])) continue;

            $username = trim($cols[0]);
            $password = isset($cols[1]) ? trim($cols[1]) : $username;
            $profile = isset($cols[2]) ? trim($cols[2]) : $defaultProfile;
            $uptime = $cols[3] ?? '';
            $bytes = $cols[4] ?? '';

            $res = $this->conn->write('/ip/hotspot/user/add', [
                'name'              => $username,
                'password'          => $password,
                'profile'           => $profile,
                'limit-uptime'      => $uptime,
                'limit-bytes-total' => $bytes,
                'comment'           => 'csv-import',
            ]);

            if (isset($res['error'])) {
                $errors[] = ['username' => $username, 'error' => $res['error']];
            } else {
                $imported++;
                if ($this->db) {
                    $this->db->insert('vouchers', [
                        'username'          => $username,
                        'password'          => $password,
                        'profile'           => $profile,
                        'limit_uptime'      => $uptime,
                        'limit_bytes_total' => $bytes,
                        'status'            => 'active',
                        'batch_tag'         => 'csv-import',
                        'created_at'        => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Render thermal receipt format (ESC/POS style for 58mm/80mm Bluetooth printers).
     *
     * @param array $voucher
     * @param array $storeInfo
     * @return string
     */
    public function renderThermalReceipt(array $voucher, array $storeInfo = []): string
    {
        if (isset($voucher['vouchers']) && is_array($voucher['vouchers'])) {
            $voucher = $voucher['vouchers'][0] ?? [];
        }

        $name = $storeInfo['store_name'] ?? ($storeInfo['name'] ?? 'HOTSPOT WI-FI');
        $address = $storeInfo['address'] ?? 'Internet Voucher';
        $loginUrl = $storeInfo['login_url'] ?? 'http://hotspot.net';
        $user = $voucher['username'] ?? '';
        $pass = $voucher['password'] ?? '';
        $profile = $voucher['profile'] ?? '';
        $limit = $voucher['limit_uptime'] ?? ($voucher['limit_bytes_total'] ?? '');
        $price = !empty($voucher['price']) ? 'Rp ' . number_format((float)$voucher['price'], 0, ',', '.') : '';

        $r = "================================\n";
        $r .= "         " . strtoupper($name) . "\n";
        $r .= "       " . $address . "\n";
        $r .= "================================\n";
        $r .= "Paket     : " . $profile . "\n";
        if ($limit) $r .= "Batas     : " . $limit . "\n";
        if ($price) $r .= "Harga     : " . $price . "\n";
        $r .= "--------------------------------\n";
        $r .= "USERNAME  : " . $user . "\n";
        if ($user !== $pass) {
            $r .= "PASSWORD  : " . $pass . "\n";
        }
        $r .= "--------------------------------\n";
        $r .= "Login ke  : " . $loginUrl . "\n";
        $r .= "Waktu     : " . date('Y-m-d H:i') . "\n";
        $r .= "================================\n";
        $r .= "   Terima Kasih & Selamat   \n";
        $r .= "          Menikmati         \n\n\n";

        return $r;
    }

    /**
     * Render printable voucher cards HTML with QR Code auto-login support.
     *
     * @param array $vouchers
     * @param array $options [
     *    'title' => string,
     *    'dns_name' => string,
     *    'price' => string,
     *    'show_qr' => bool (default true)
     * ]
     * @return string
     */
    public function renderPrintCards(array $vouchers, array $options = []): string
    {
        if (isset($vouchers['vouchers']) && is_array($vouchers['vouchers'])) {
            $vouchers = $vouchers['vouchers'];
        }

        $title = htmlspecialchars($options['title'] ?? 'MIKROTIK HOTSPOT');
        $dnsName = htmlspecialchars($options['dns_name'] ?? 'http://hotspot.net');
        $defaultPrice = $options['price'] ?? '';
        $showQr = $options['show_qr'] ?? true;

        $html = '<style>
            .voucher-grid { display: flex; flex-wrap: wrap; gap: 10px; font-family: monospace; }
            .voucher-card { border: 2px dashed #222; border-radius: 6px; padding: 10px; width: 195px; background: #fff; box-sizing: border-box; text-align: center; }
            .voucher-header { font-size: 13px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
            .voucher-body { margin: 8px 0; font-size: 12px; text-align: left; }
            .voucher-code { font-size: 15px; font-weight: bold; text-align: center; background: #f3f4f6; padding: 4px; border-radius: 4px; margin: 4px 0; }
            .voucher-qr { margin: 6px auto; text-align: center; }
            .voucher-footer { font-size: 10px; color: #555; border-top: 1px solid #ddd; padding-top: 4px; }
            @media print { .no-print { display: none; } }
        </style>';
        $html .= '<div class="no-print" style="margin-bottom:15px;"><button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">🖨️ Cetak Voucher</button></div>';
        $html .= '<div class="voucher-grid">';

        foreach ($vouchers as $v) {
            if (!is_array($v)) continue;
            $user = htmlspecialchars($v['username'] ?? '');
            $pass = htmlspecialchars($v['password'] ?? '');
            $profile = htmlspecialchars($v['profile'] ?? '');
            $limit = htmlspecialchars($v['limit_uptime'] ?? ($v['limit_bytes_total'] ?? ''));
            $price = !empty($v['price']) ? 'Rp ' . number_format((float)$v['price'], 0, ',', '.') : $defaultPrice;

            $qrImg = '';
            if ($showQr && !empty($user)) {
                $qrUrl = $this->generateQrCodeUrl($user, $pass, $options['dns_name'] ?? 'http://hotspot.net', 90);
                $qrImg = '<div class="voucher-qr"><img src="' . htmlspecialchars($qrUrl) . '" width="80" height="80" alt="QR Scan" /></div>';
            }

            $html .= '<div class="voucher-card">';
            $html .= '<div class="voucher-header">' . $title . '</div>';
            $html .= '<div class="voucher-body">';
            $html .= '<div>User: <b>' . $user . '</b></div>';
            if ($user !== $pass) {
                $html .= '<div>Pass: <b>' . $pass . '</b></div>';
            } else {
                $html .= '<div class="voucher-code">' . $user . '</div>';
            }
            if ($profile) $html .= '<div>Paket: ' . $profile . '</div>';
            if ($limit) $html .= '<div>Batas: ' . $limit . '</div>';
            if ($price) $html .= '<div>Harga: ' . htmlspecialchars((string)$price) . '</div>';
            $html .= '</div>';
            $html .= $qrImg;
            $html .= '<div class="voucher-footer">Login: ' . $dnsName . '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }
}
