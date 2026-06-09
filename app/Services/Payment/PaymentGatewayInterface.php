<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Thực hiện quy trình thanh toán
     * 
     * @param int $amount Số tiền thanh toán (VND)
     * @param string $orderId Mã đơn hàng / Booking ID
     * @return array Trả về mảng chứa status và transaction_id
     */
    public function processPayment(int $amount, string $orderId): array;
}
