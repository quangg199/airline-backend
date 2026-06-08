<?php

namespace App\Services\Pricing;

use App\Contracts\PricingStrategyInterface;

/**
 * OneWayPricingStrategy (Concrete Strategy)
 *
 * Chiến lược tính giá cho vé MỘT CHIỀU (one-way).
 * Đây là chiến lược mặc định — giá hiển thị = giá gốc, không áp dụng
 * bất kỳ chiết khấu hay phụ phí nào.
 *
 * Tại sao cần class riêng thay vì hardcode?
 * → Tuân thủ OCP: Nếu sau này cần thêm phụ phí cho vé một chiều
 *   (VD: phí tiện ích), chỉ cần sửa class này mà không ảnh hưởng
 *   đến RoundTripPricingStrategy hay bất kỳ module nào khác.
 */
class OneWayPricingStrategy implements PricingStrategyInterface
{
    /**
     * Giá một chiều = giá gốc (identity function).
     *
     * @param  float  $basePrice  Giá gốc từ DB (VND).
     * @return float  Giá hiển thị — không thay đổi.
     */
    public function calculate(float $basePrice): float
    {
        return $basePrice;
    }
}
