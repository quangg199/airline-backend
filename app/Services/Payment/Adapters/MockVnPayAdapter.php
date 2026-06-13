<?php

namespace App\Services\Payment\Adapters;

use App\Services\Payment\PaymentGatewayInterface;

class MockVnPayAdapter implements PaymentGatewayInterface
{
    /**
     * Mô phỏng gọi API sang server VNPay
     */
    public function processPayment(int $amount, string $orderId): array
    {
        // 1. Mô phỏng độ trễ của API thực tế (1 giây)
        sleep(1);

        // 2. Logic hash theo chuẩn VNPAY (vnp_SecureHash)
        $secureHash = md5("vnp_Amount=$amount&vnp_TxnRef=$orderId" . 'vnp_hash_secret');

        // 3. Trả về mock data từ VNPay
        return [
            'status'         => 'success',
            'transaction_id' => 'VNPAY_TXN_' . strtoupper(uniqid()),
            'gateway'        => 'vnpay',
            'message'        => 'Thanh toán thành công qua cổng VNPay.',
            'signature'      => $secureHash,
        ];
    }
}
