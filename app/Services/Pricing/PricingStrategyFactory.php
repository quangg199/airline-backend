<?php

namespace App\Services\Pricing;

use App\Contracts\PricingStrategyInterface;
use InvalidArgumentException;

/**
 * PricingStrategyFactory (Simple Factory — Creational)
 *
 * Chịu trách nhiệm duy nhất: Ánh xạ chuỗi trip_type từ HTTP request
 * sang đúng Concrete Strategy tương ứng tại runtime.
 *
 * Tại sao cần Factory thay vì if/else trong Controller?
 * 1. SRP: Controller không nên biết cách khởi tạo Strategy.
 * 2. OCP: Khi thêm loại vé mới (VD: 'multi_city'), chỉ cần thêm
 *    1 case vào match() + 1 class mới — Controller không bị sửa.
 * 3. Testability: Có thể mock Factory trong unit test.
 *
 * PHP 8.0 match expression:
 * - Giống switch nhưng strict comparison (===), không cần break.
 * - Throw exception tự động khi không match (UnhandledMatchError),
 *   nhưng ta dùng default để throw InvalidArgumentException rõ ràng hơn.
 */
class PricingStrategyFactory
{
    /**
     * Phân giải (resolve) Strategy từ chuỗi trip_type.
     *
     * @param  string  $tripType  Loại vé: 'one_way' hoặc 'round_trip'.
     * @return PricingStrategyInterface  Concrete Strategy tương ứng.
     *
     * @throws InvalidArgumentException  Khi trip_type không hợp lệ.
     */
    public static function resolve(string $tripType): PricingStrategyInterface
    {
        return match ($tripType) {
            'one_way'    => new OneWayPricingStrategy(),
            'round_trip' => new RoundTripPricingStrategy(),
            default      => throw new InvalidArgumentException(
                "Loại vé không hợp lệ: '{$tripType}'. Chỉ hỗ trợ: one_way, round_trip."
            ),
        };
    }
}
