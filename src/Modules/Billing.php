<?php

namespace RouterOSFramework\Modules;

use RouterOSFramework\Core\Database;
use RouterOSFramework\Core\JsonStorage;
use RouterOSFramework\Core\RouterOS;

/**
 * Class Billing
 * Automated payment gateway integration & voucher fulfillment (Tripay, Midtrans, Duitku, Xendit, QRIS).
 */
class Billing
{
    private RouterOS $ros;
    private ?Database $db;
    private JsonStorage $json;

    public function __construct(RouterOS $ros)
    {
        $this->ros = $ros;
        $this->db = $ros->db();
        $this->json = $ros->json();
    }

    /**
     * Process automated payment webhook from Tripay, Midtrans, Duitku, or Xendit.
     *
     * @param string $gateway ('tripay'|'midtrans'|'duitku'|'xendit'|'generic')
     * @param array $payload Webhook POST data
     * @param array $packageConfig Package mapping (e.g. ['PAKET-2JAM' => ['profile' => 'default', 'limit-uptime' => '2h', 'price' => 5000]])
     * @return array
     */
    public function handleWebhook(string $gateway, array $payload, array $packageConfig = []): array
    {
        $isPaid = false;
        $orderId = '';
        $amount = 0;
        $packageCode = '';
        $customerPhone = '';

        switch (strtolower($gateway)) {
            case 'tripay':
                $status = $payload['status'] ?? '';
                $isPaid = ($status === 'PAID');
                $orderId = $payload['merchant_ref'] ?? '';
                $amount = (float)($payload['total_amount'] ?? 0);
                $customerPhone = $payload['customer_phone'] ?? '';
                $packageCode = $payload['order_items'][0]['sku'] ?? ($payload['order_items'][0]['name'] ?? 'DEFAULT');
                break;

            case 'midtrans':
                $status = $payload['transaction_status'] ?? '';
                $isPaid = ($status === 'settlement' || $status === 'capture');
                $orderId = $payload['order_id'] ?? '';
                $amount = (float)($payload['gross_amount'] ?? 0);
                break;

            case 'duitku':
                $resultCode = $payload['resultCode'] ?? '';
                $isPaid = ($resultCode === '00');
                $orderId = $payload['merchantOrderId'] ?? '';
                $amount = (float)($payload['amount'] ?? 0);
                break;

            default:
                // Generic / Custom Webhook
                $isPaid = !empty($payload['paid']) || ($payload['status'] ?? '') === 'SUCCESS';
                $orderId = $payload['order_id'] ?? ('ORD-' . time());
                $amount = (float)($payload['amount'] ?? 0);
                $packageCode = $payload['package'] ?? 'DEFAULT';
                $customerPhone = $payload['phone'] ?? '';
                break;
        }

        if (!$isPaid) {
            return [
                'success' => false,
                'status'  => 'unpaid',
                'message' => 'Payment status is not confirmed as PAID.',
            ];
        }

        // Determine profile & limit from package configuration
        $pkg = $packageConfig[$packageCode] ?? [
            'profile'      => 'default',
            'limit-uptime' => '2h',
            'price'        => $amount,
        ];

        // Generate voucher on MikroTik and save to Database
        $batch = $this->ros->voucher()->generateAndSave(1, [
            'prefix'            => 'VC-',
            'profile'           => $pkg['profile'] ?? 'default',
            'limit-uptime'      => $pkg['limit-uptime'] ?? '2h',
            'limit-bytes-total' => $pkg['limit-bytes-total'] ?? '',
            'price'             => $amount,
            'batch_tag'         => 'pay-' . $orderId,
            'comment'           => "Order #{$orderId}",
        ]);

        $voucher = !empty($batch['vouchers']) ? $batch['vouchers'][0] : null;

        if ($voucher) {
            // Record payment transaction in JSON & SQL
            $trxData = [
                'order_id'       => $orderId,
                'gateway'        => $gateway,
                'amount'         => $amount,
                'customer_phone' => $customerPhone,
                'voucher_user'   => $voucher['username'],
                'voucher_pass'   => $voucher['password'],
                'package'        => $packageCode,
                'status'         => 'COMPLETED',
                'created_at'     => date('Y-m-d H:i:s'),
            ];

            $this->json->push('billing_transactions', $trxData);

            if ($this->db) {
                $this->db->log('Billing', 'Auto Voucher Payment', $orderId, "Issued voucher {$voucher['username']}", 'success');
            }

            // Generate WhatsApp notification text template
            $waMessage = "✅ *PEMBAYARAN VOUCHER BERHASIL!*\n"
                . "Order: #{$orderId}\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "🔑 *Username:* `{$voucher['username']}`\n"
                . "🔐 *Password:* `{$voucher['password']}`\n"
                . "⏱️ *Paket:* {$pkg['limit-uptime']}\n"
                . "💵 *Total:* Rp " . number_format($amount, 0, ',', '.') . "\n\n"
                . "Silakan hubungkan perangkat Anda ke WiFi dan masukkan Username di halaman login.";

            return [
                'success'    => true,
                'status'     => 'success',
                'order_id'   => $orderId,
                'voucher'    => $voucher,
                'wa_message' => $waMessage,
            ];
        }

        return [
            'success' => false,
            'status'  => 'error',
            'message' => 'Failed to generate voucher on MikroTik.',
        ];
    }
}
