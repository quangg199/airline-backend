<?php

namespace App\Services\Payment;

use App\Services\Payment\Adapters\MockMomoAdapter;
use App\Services\Payment\Adapters\MockVnPayAdapter;
use InvalidArgumentException;

class PaymentFactory
{
    /**
     * Resolves the correct payment adapter based on the gateway string.
     * 
     * @param string $gateway
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException
     */
    public static function create(string $gateway): PaymentGatewayInterface
    {
        return match (strtolower($gateway)) {
            'momo'  => new MockMomoAdapter(),
            'vnpay' => new MockVnPayAdapter(),
            default => throw new InvalidArgumentException("Phương thức thanh toán [{$gateway}] không được hỗ trợ."),
        };
    }
}
