<?php
/**
 * =========================================================================
 * Project RouterOS Framework - Bot Webhook Handler (Telegram & WhatsApp)
 * =========================================================================
 *
 * Webhook handler siap pakai untuk Bot Telegram & WhatsApp Gateway.
 * Menerima pesan teks masuk, memproses perintah, dan membalas secara otomatis.
 */

require __DIR__ . '/vendor/autoload.php';

use RouterOSFramework\Core\RouterOS;
use RouterOSFramework\Core\Response;

// Load framework
$ros = RouterOS::withConfig(__DIR__ . '/config.php');

// Ambil input JSON dari Telegram atau WhatsApp Gateway
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$messageText = '';
$chatId = '';
$sender = '';

// 1. Format Payload Telegram Bot
if (isset($data['message']['text'])) {
    $messageText = $data['message']['text'];
    $chatId = $data['message']['chat']['id'] ?? '';
    $sender = $data['message']['from']['first_name'] ?? 'User';
}
// 2. Format Payload WhatsApp Gateway (Fonnte / Generic)
elseif (isset($data['message'])) {
    $messageText = is_string($data['message']) ? $data['message'] : '';
    $chatId = $data['sender'] ?? ($data['from'] ?? '');
    $sender = $data['name'] ?? $chatId;
}
// 3. Fallback Test via Query / Form
elseif (isset($_GET['cmd'])) {
    $messageText = $_GET['cmd'];
    $sender = 'Tester';
}

if (empty($messageText)) {
    Response::json(['status' => 'waiting', 'hint' => 'Send Telegram/WhatsApp webhook payload or ?cmd=/status'], 200);
}

// Proses perintah melalui BotHandler Framework
$botResult = $ros->bot()->handle($messageText, $sender);

// Contoh: Jika Telegram Bot Token disetel, kirim balasan langsung via Telegram API
$telegramToken = getenv('TELEGRAM_BOT_TOKEN') ?: '';
if ($telegramToken && $chatId) {
    $sendUrl = "https://api.telegram.org/bot{$telegramToken}/sendMessage";
    $postFields = [
        'chat_id'    => $chatId,
        'text'       => $botResult['text'],
        'parse_mode' => 'Markdown',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $sendUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// Kembalikan response JSON
Response::json($botResult, 200, 'Bot command processed');
