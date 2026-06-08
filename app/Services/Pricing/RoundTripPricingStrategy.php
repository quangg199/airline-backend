<?php

namespace App\Services\Pricing;

use App\Contracts\PricingStrategyInterface;

/**
 * RoundTripPricingStrategy (Concrete Strategy)
 *
 * Chiến lược tính giá cho vé KHỨ HỒI (round-trip).
 * Áp dụng chiết khấu 10% trên giá gốc để khuyến khích khách hàng
 * mua vé khứ hồi thay vì hai vé một chiều riêng lẻ.
 *
 * Công thức: display_price = base_price × (1 - DISCOUNT_RATE)
 * Ví dụ:    base_price = 2,000,000 VND → display_price = 1,800,000 VND
 *
 * Tại sao dùng class constant cho DISCOUNT_RATE?
 * → Tránh "magic number" rải rác trong code. Khi cần thay đổi tỉ lệ
 *   chiết khấu, chỉ cần sửa MỘT chỗ duy nhất (Single Source of Truth).
 */
class RoundTripPricingStrategy implements PricingStrategyInterface
{
    /**
     * Tỉ lệ chiết khấu cho vé khứ hồi.
     * 0.10 = 10%.
     */
    private const DISCOUNT_RATE = 0.10;

    /**
     * Giá khứ hồi = giá gốc × 90% (giảm 10%).
     * Dùng round() để tránh số lẻ floating-point (VD: 1800000.0000001).
     *
     * @param  float  $basePrice  Giá gốc từ DB (VND).
     * @return float  Giá sau chiết khấu 10%.
     */
    public function calculate(float $basePrice): float
    {
        return round($basePrice * (1 - self::DISCOUNT_RATE));
    }
}
