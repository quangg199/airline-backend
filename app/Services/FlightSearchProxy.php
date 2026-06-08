<?php

namespace App\Services;

use App\Contracts\PricingStrategyInterface;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\User;
use App\Services\Pricing\PricingStrategyFactory;
use App\Services\Pricing\VipPricingDecorator;
use Illuminate\Support\Collection;

/**
 * FlightSearchProxy (Proxy Pattern — Structural)
 *
 * Đóng vai trò là tầng trung gian (Proxy) giữa Controller và Database.
 * Thay vì Controller truy vấn thẳng DB, Proxy kiểm tra sự tồn tại
 * của dữ liệu trước, rồi quyết định hành động phù hợp.
 *
 * Vấn đề giải quyết (Problem-First):
 * ─────────────────────────────────
 * FlightController trước đây query thẳng DB → nếu DB trống cho
 * tuyến+ngày đó → trả về mảng rỗng → UX tệ.
 *
 * Tại sao chọn Proxy Pattern?
 * ─────────────────────────────
 * - Proxy = "người đại diện" đứng trước Real Subject (Database).
 * - Proxy có thể: (1) kiểm tra trước, (2) tạo dữ liệu lazy nếu thiếu,
 *   (3) áp dụng pricing rồi mới trả kết quả.
 * - Controller KHÔNG biết data đến từ DB có sẵn hay mới generate.
 *   → Encapsulation hoàn hảo.
 *
 * Luồng xử lý (Flow):
 * ─────────────────────
 * 1. Resolve mã sân bay (code) → Airport model.
 * 2. Query DB tìm flights cho tuyến + ngày đó.
 * 3. NẾU TRỐNG → delegate sang FlightGeneratorService (lazy generation).
 * 4. Áp dụng PricingStrategy lên toàn bộ flights (one_way / round_trip).
 * 5. Trả về kết quả đã được enriched.
 *
 * SOLID Compliance:
 * - SRP : Chỉ orchestrate — không tạo data (Generator làm), không tính giá
 *         (Strategy làm), không format response (Controller làm).
 * - DIP : Phụ thuộc vào PricingStrategyInterface (abstraction),
 *         không phụ thuộc vào OneWayPricingStrategy cụ thể.
 * - OCP : Thêm nguồn dữ liệu mới (VD: external API) → tạo class mới,
 *         không sửa Proxy.
 */
class FlightSearchProxy
{
    /**
     * Inject FlightGeneratorService qua constructor (DIP).
     * Laravel IoC container tự động resolve dependency này.
     */
    public function __construct(
        private FlightGeneratorService $generator
    ) {}

    /**
     * Tìm kiếm chuyến bay với cơ chế On-Demand Generation.
     *
     * @param  string  $fromCode  Mã IATA sân bay đi (VD: 'HAN').
     * @param  string  $toCode    Mã IATA sân bay đến (VD: 'SGN').
     * @param  string  $date      Ngày khởi hành (format: YYYY-MM-DD).
     * @param  string  $tripType  Loại vé: 'one_way' hoặc 'round_trip'.
     * @return array{
     *     flights: Collection,
     *     trip_type: string,
     *     discount_applied: string|null
     * }
     */
    public function search(
        string $fromCode,
        string $toCode,
        string $date,
        string $tripType = 'one_way',
        ?User $user = null
    ): array {

        // ── Bước 1: Resolve Airport từ mã IATA ──────────────────
        // Chuyển đổi chuỗi mã sân bay (VD: 'HAN') thành Eloquent model
        // để lấy primary key (id) cho các query tiếp theo.
        $departureAirport = Airport::where('code', $fromCode)->first();
        $arrivalAirport   = Airport::where('code', $toCode)->first();

        // Guard clause: Nếu mã sân bay không tồn tại → trả về rỗng
        // thay vì throw exception (fail gracefully cho search feature).
        if (!$departureAirport || !$arrivalAirport) {
            return [
                'flights'          => collect(),
                'trip_type'        => $tripType,
                'discount_applied' => null,
            ];
        }

        // ── Bước 2: Query DB — tìm chuyến bay có sẵn ────────────
        // Đây là "Real Subject" trong Proxy Pattern.
        // Proxy kiểm tra data tồn tại TRƯỚC khi quyết định hành động.
        $flights = Flight::where('departure_airport_id', $departureAirport->id)
            ->where('arrival_airport_id', $arrivalAirport->id)
            ->whereDate('departure_time', $date)
            ->with(['departureAirport', 'arrivalAirport', 'aircraft'])
            ->get();

        // ── Bước 3: Proxy Intercept — Lazy Generation ────────────
        // NẾU DB trống cho tuyến+ngày này → delegate sang Generator.
        // Sau khi generate, data đã được persist vào DB.
        // → Lần search tiếp theo sẽ hit Bước 2 mà không cần generate lại.
        if ($flights->isEmpty()) {
            $flights = $this->generator->generate(
                $departureAirport,
                $arrivalAirport,
                $date
            );
        }

        // ── Bước 4: Áp dụng Pricing Strategy ────────────────────
        // PricingStrategyFactory resolve đúng strategy dựa trên trip_type.
        // Strategy tính display_price mà KHÔNG thay đổi base_price trong DB.
        $strategy = PricingStrategyFactory::resolve($tripType);

        // Decorator Pattern: Nếu user là VIP, bọc Strategy bằng VipPricingDecorator
        if ($user && $user->is_vip) {
            $strategy = new VipPricingDecorator($strategy);
        }

        $flights  = $this->applyPricing($flights, $strategy);

        // Xác định discount applied (string) để trả về cho Frontend hiển thị nhãn
        $discountLabel = null;
        if ($user && $user->is_vip) {
            $discountLabel = 'VIP 5%';
        } elseif ($tripType === 'round_trip') {
            $discountLabel = '10%';
        }

        return [
            'flights'          => $flights,
            'trip_type'        => $tripType,
            'discount_applied' => $discountLabel,
        ];
    }

    /**
     * Áp dụng chiến lược tính giá lên từng chuyến bay.
     *
     * Thêm attribute 'display_price' vào mỗi Flight model mà KHÔNG
     * ghi đè 'base_price' gốc → base_price trong DB luôn là "truth",
     * display_price là giá đã qua strategy (có thể có discount).
     *
     * @param  Collection               $flights   Danh sách chuyến bay.
     * @param  PricingStrategyInterface  $strategy  Strategy đã resolve.
     * @return Collection  Flights với attribute 'display_price' được thêm.
     */
    private function applyPricing(Collection $flights, PricingStrategyInterface $strategy): Collection
    {
        return $flights->map(function (Flight $flight) use ($strategy) {
            // setAttribute thêm dynamic attribute vào model instance
            // mà không trigger Eloquent save() → DB không bị ảnh hưởng.
            $flight->setAttribute('display_price', $strategy->calculate((float) $flight->base_price));
            return $flight;
        });
    }
}
