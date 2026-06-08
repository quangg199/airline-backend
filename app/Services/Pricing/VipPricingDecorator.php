<?php

namespace App\Services\Pricing;

use App\Contracts\PricingStrategyInterface;

/**
 * VipPricingDecorator (Decorator Pattern — Structural)
 *
 * Mở rộng động quy tắc tính giá bằng cách bọc (wrap) quanh một 
 * PricingStrategyInterface bất kỳ (VD: OneWay hoặc RoundTrip).
 *
 * Vấn đề: 
 * Khi khách hàng đạt cấp VIP, họ được giảm thêm 5% giá vé cơ bản.
 * Nếu chèn logic `if (is_vip)` vào từng Strategy thì vi phạm Open-Closed Principle.
 * 
 * Giải pháp (Decorator):
 * 1. Bọc Strategy hiện tại vào trong Decorator này.
 * 2. Gọi hàm tính toán của Strategy hiện tại.
 * 3. Áp dụng thêm phần giảm giá của VIP ở kết quả cuối cùng.
 */
class VipPricingDecorator implements PricingStrategyInterface
{
    /**
     * @param PricingStrategyInterface $baseStrategy Chiến lược gốc cần được bọc (Trang bị thêm)
     */
    public function __construct(
        protected PricingStrategyInterface $baseStrategy
    ) {}

    /**
     * Tính giá mới: Gọi Strategy bên trong, sau đó giảm 5%.
     *
     * @param  float  $basePrice
     * @return float
     */
    public function calculate(float $basePrice): float
    {
        // 1. Tính giá cơ bản thông qua chiến lược gốc
        $price = $this->baseStrategy->calculate($basePrice);

        // 2. Giảm 5% cho VIP
        return $price * 0.95;
    }
}
