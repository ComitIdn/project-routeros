<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Helper;
use RouterOSFramework\Core\RouterOS;

/**
 * Class BotHandler
 * Engine for building Telegram, WhatsApp, and Discord RouterOS bots with formatted emoji replies.
 */
class BotHandler
{
    private RouterOS $ros;

    public function __construct(RouterOS $ros)
    {
        $this->ros = $ros;
    }

    /**
     * Parse incoming bot text command and return structured response.
     *
     * @param string $message Incoming chat message (e.g. "/status", "/vc 5 default 2h", "/kick user1")
     * @param string|null $sender Sender name / phone / chat_id
     * @return array ['success' => bool, 'text' => string, 'action' => string, 'data' => mixed]
     */
    public function handle(string $message, ?string $sender = null): array
    {
        $message = trim($message);
        $parts = preg_split('/\s+/', $message);
        $cmd = strtolower($parts[0] ?? '');

        // Remove prefix symbols like / or !
        $cleanCmd = ltrim($cmd, '/!#.');

        switch ($cleanCmd) {
            case 'start':
            case 'help':
            case 'menu':
                return [
                    'success' => true,
                    'action'  => 'help',
                    'text'    => $this->formatHelpMessage(),
                ];

            case 'status':
            case 'ping':
            case 'info':
                return [
                    'success' => true,
                    'action'  => 'status',
                    'text'    => $this->formatStatusMessage(),
                ];

            case 'traffic':
            case 'rx':
            case 'tx':
                return [
                    'success' => true,
                    'action'  => 'traffic',
                    'text'    => $this->formatTrafficMessage(),
                ];

            case 'vc':
            case 'voucher':
            case 'gen':
                // Usage: /vc [count] [profile] [limit_uptime] [limit_bytes]
                $count = isset($parts[1]) ? max(1, min(50, (int)$parts[1])) : 5;
                $profile = $parts[2] ?? 'default';
                $limitUptime = $parts[3] ?? '';
                $limitBytes = $parts[4] ?? '';

                $options = [
                    'prefix'    => 'VC-',
                    'profile'   => $profile,
                    'batch_tag' => 'bot-' . ($sender ? substr($sender, -4) . '-' : '') . date('Ymd-His'),
                ];
                if (!empty($limitUptime)) $options['limit-uptime'] = $limitUptime;
                if (!empty($limitBytes)) $options['limit-bytes-total'] = $limitBytes;

                $batch = $this->ros->voucher()->generateAndSave($count, $options);
                return [
                    'success' => !empty($batch['vouchers']),
                    'action'  => 'voucher',
                    'text'    => $this->formatVoucherCreatedMessage($batch['vouchers'], $batch['batch_tag']),
                    'data'    => $batch,
                ];

            case 'kick':
            case 'disconnect':
                // Usage: /kick [username]
                $target = $parts[1] ?? '';
                if (empty($target)) {
                    return [
                        'success' => false,
                        'text'    => "⚠️ Format salah! Gunakan: `/kick [username]`",
                    ];
                }
                $res = $this->ros->hotspot()->disconnectUser($target);
                return [
                    'success' => true,
                    'action'  => 'kick',
                    'text'    => "⚡ Pengguna *{$target}* berhasil diputuskan! ({$res['disconnected']} sesi ditutup).",
                ];

            case 'bypass':
                // Usage: /bypass [mac_address] [comment]
                $mac = $parts[1] ?? '';
                $comment = isset($parts[2]) ? implode(' ', array_slice($parts, 2)) : 'Bypass via Bot';
                if (!Helper::isValidMac($mac)) {
                    return [
                        'success' => false,
                        'text'    => "⚠️ Format MAC Address tidak valid! Contoh: `00:11:22:33:44:55`",
                    ];
                }
                $res = $this->ros->hotspot()->bypassDevice($mac, null, $comment);
                return [
                    'success' => !isset($res['error']),
                    'action'  => 'bypass',
                    'text'    => !isset($res['error'])
                        ? "✅ Perangkat `{$mac}` berhasil di-bypass tanpa login ({$comment})!"
                        : "❌ Gagal bypass: " . $res['error'],
                ];

            default:
                return [
                    'success' => false,
                    'action'  => 'unknown',
                    'text'    => "❓ Perintah tidak dikenali. Ketik `/help` untuk melihat daftar perintah bot.",
                ];
        }
    }

    public function formatHelpMessage(): string
    {
        return "🤖 *ROUTEROS BOT COMMANDS* ⚡\n\n"
            . "📊 `/status` - Cek CPU, RAM, Uptime & user aktif\n"
            . "🌐 `/traffic` - Pantau trafik bandwidth live\n"
            . "🎫 `/vc [jumlah] [profil] [waktu]` - Buat voucher Hotspot\n"
            . "   _Contoh: `/vc 5 default 2h`_\n"
            . "⚡ `/kick [username]` - Putuskan user aktif\n"
            . "🔓 `/bypass [mac] [nama]` - Bypass perangkat tanpa login\n"
            . "❓ `/help` - Tampilkan panduan ini";
    }

    public function formatStatusMessage(): string
    {
        $res = $this->ros->system()->getResources();
        $identity = $this->ros->system()->getIdentity();
        $summary = $this->ros->hotspot()->getSummary();

        $name = $identity[0]['name'] ?? 'MikroTik';
        $cpu = $res[0]['cpu-load'] ?? '0';
        $uptime = $res[0]['uptime'] ?? '-';
        $board = $res[0]['board-name'] ?? '-';
        $freeMem = isset($res[0]['free-memory']) ? Helper::formatBytes((float)$res[0]['free-memory']) : '-';

        return "📡 *STATUS ROUTEROS: {$name}*\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "🖥️ *Board:* {$board}\n"
            . "⚙️ *CPU Load:* {$cpu}%\n"
            . "💾 *Free RAM:* {$freeMem}\n"
            . "⏱️ *Uptime:* {$uptime}\n\n"
            . "👥 *Hotspot Users:* {$summary['total_users']}\n"
            . "🟢 *Online Aktif:* {$summary['active_users']}\n"
            . "📱 *Perangkat Terdeteksi:* {$summary['total_hosts']}\n"
            . "🔓 *Bypassed Devices:* {$summary['bypassed_hosts']}";
    }

    public function formatTrafficMessage(): string
    {
        $traffic = $this->ros->hotspot()->getActiveTraffic();
        if (empty($traffic) || isset($traffic['error'])) {
            return "ℹ️ Belum ada sesi Hotspot aktif yang menggunakan bandwidth.";
        }

        $msg = "🌐 *TRAFIK PENGGUNA AKTIF*\n━━━━━━━━━━━━━━━━━━━━\n";
        foreach (array_slice($traffic, 0, 10) as $t) {
            $msg .= "👤 *{$t['user']}* (`{$t['address']}`)\n";
            $msg .= "   ↓ {$t['bytes_in_human']} | ↑ {$t['bytes_out_human']} (⏱️ {$t['uptime']})\n";
        }
        return $msg;
    }

    public function formatVoucherCreatedMessage(array $vouchers, string $batchTag): string
    {
        $count = count($vouchers);
        $msg = "🎫 *VOUCHER BERHASIL DIBUAT ({$count})*\n"
            . "Tag Batch: `{$batchTag}`\n"
            . "━━━━━━━━━━━━━━━━━━━━\n";

        foreach ($vouchers as $v) {
            $user = $v['username'];
            $pass = $v['password'];
            $profile = $v['profile'];
            $limit = $v['limit_uptime'] ?: ($v['limit_bytes_total'] ?: 'Unlimited');

            $msg .= "🔑 User: `{$user}`";
            if ($user !== $pass) {
                $msg .= " | Pass: `{$pass}`";
            }
            $msg .= " ({$profile} - {$limit})\n";
        }

        $msg .= "\n_Login melalui portal Hotspot lokal._";
        return $msg;
    }
}
