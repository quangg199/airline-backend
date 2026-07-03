<?php

namespace App\Contracts;


interface PricingStrategyInterface
{
    /**
     * Tính giá hiển thị từ giá gốc (base_price) của chuyến bay.
     *
     * @param  float  $basePrice  Giá gốc lưu trong DB (đơn vị: VND).
     * @return float  Giá sau khi áp dụng quy tắc của strategy (đơn vị: VND).
     */
    public function calculate(float $basePrice): float;
}
