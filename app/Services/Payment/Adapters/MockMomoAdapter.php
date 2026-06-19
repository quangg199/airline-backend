<?php

namespace App\Services\Payment\Adapters;

use App\Services\Payment\PaymentGatewayInterface;

class MockMomoAdapter implements PaymentGatewayInterface
{
    /**
     * Mô phỏng gọi API sang server Momo
     */
    public function processPayment(int $amount, string $orderId): array
    {
        // 1. Mô phỏng độ trễ của API thực tế (1 giây)
        sleep(1);

        // 2. Logic hash signature bằng HMAC SHA256 (Mô phỏng)
        $signature = hash_hmac('sha256', "amount=$amount&orderId=$orderId", 'secret_key');

        // 3. Trả về mock data từ Momo
        return [
            'status'         => 'success',
            'transaction_id' => 'MOMO_PAY_' . strtoupper(uniqid()),
            'gateway'        => 'momo',
            'message'        => 'Thanh toán thành công qua ví Momo.',
            'signature'      => $signature,
        ];
    }
}
