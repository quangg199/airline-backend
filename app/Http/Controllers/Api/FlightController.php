<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use App\Models\Seat;
use App\Services\FlightSearchProxy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * FlightController (Skinny Controller — SRP)
 *
 * Chịu trách nhiệm duy nhất: Nhận HTTP input → delegate sang Service →
 * format HTTP output. KHÔNG chứa business logic.
 *
 * Kiến trúc On-Demand Dynamic Generation:
 * ─────────────────────────────────────────
 * Controller → FlightSearchProxy (Proxy Pattern)
 *                ├── DB Query (Real Subject)
 *                ├── FlightGeneratorService (Factory) ← nếu DB trống
 *                └── PricingStrategyFactory → Strategy (one_way / round_trip)
 *
 * FlightSearchProxy được inject qua constructor (DIP).
 * Laravel IoC container tự động resolve toàn bộ dependency chain:
 *   FlightController → FlightSearchProxy → FlightGeneratorService
 */
class FlightController extends Controller
{
    /**
     * Constructor Injection (DIP — Dependency Inversion Principle).
     *
     * Controller phụ thuộc vào FlightSearchProxy (concrete class),
     * nhưng Proxy bên trong phụ thuộc vào PricingStrategyInterface
     * (abstraction) → đảm bảo DIP ở tầng business logic.
     */
    public function __construct(
        private FlightSearchProxy $searchProxy
    ) {}

    /**
     * Tìm kiếm chuyến bay.
     *
     * Hai chế độ hoạt động:
     * ─────────────────────
     * 1. FULL SEARCH (from + to + date có đủ):
     *    → Sử dụng FlightSearchProxy với On-Demand Generation.
     *    → Nếu DB trống cho tuyến+ngày → tự động sinh 5 chuyến bay.
     *    → Áp dụng PricingStrategy dựa trên trip_type.
     *
     * 2. GENERAL LISTING (thiếu params):
     *    → Fallback: query DB thông thường với các filter có sẵn.
     *    → Không trigger On-Demand Generation.
     *
     * @param  Request  $request  HTTP request chứa query params:
     *                            - from: Mã sân bay đi (VD: 'HAN')
     *                            - to: Mã sân bay đến (VD: 'SGN')
     *                            - date: Ngày khởi hành (VD: '2026-07-20')
     *                            - trip_type: 'one_way' | 'round_trip' (mặc định: 'one_way')
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // ── Chế độ 1: Full Search — On-Demand Proxy ──────────────
        // Khi FE gửi đủ 3 params (from, to, date), kích hoạt Proxy.
        // filled() kiểm tra params tồn tại VÀ không rỗng (khác has()).
        if ($request->filled(['from', 'to', 'date'])) {

            // trip_type: mặc định 'one_way' nếu FE không gửi
            $tripType = $request->input('trip_type', 'one_way');

            // Delegate hoàn toàn cho Proxy — Controller không biết
            // data đến từ DB sẵn có hay mới được generate.
            $result = $this->searchProxy->search(
                fromCode: $request->input('from'),
                toCode:   $request->input('to'),
                date:     $request->input('date'),
                tripType: $tripType,
                user:     $request->user('sanctum')
            );

            return response()->json([
                'status'           => 'success',
                'results'          => $result['flights']->count(),
                'trip_type'        => $result['trip_type'],
                'discount_applied' => $result['discount_applied'],
                'data'             => $result['flights'],
            ]);
        }

        // ── Chế độ 2: General Listing — Fallback ────────────────
        // Khi FE chỉ gửi một phần params hoặc không gửi gì.
        // Dùng Eager Loading để tránh N+1 query.
        $query = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft']);

        if ($request->has('from')) {
            $query->whereHas('departureAirport', fn($q) => $q->where('code', $request->from));
        }
        if ($request->has('to')) {
            $query->whereHas('arrivalAirport', fn($q) => $q->where('code', $request->to));
        }
        if ($request->filled('date')) {
            $query->whereDate('departure_time', $request->date);
        }

        $flights = $query->latest()->get();

        return response()->json([
            'status'  => 'success',
            'results' => $flights->count(),
            'data'    => $flights,
        ]);
    }

    /**
     * Lấy sơ đồ ghế của chuyến bay và trạng thái khóa
     * GET /api/flights/{id}/seats
     */
    public function seats(\Illuminate\Http\Request $request, $id): JsonResponse
    {
        $flight = Flight::with('aircraft')->findOrFail($id);
        
        // Tất cả ghế của máy bay này
        $seats = Seat::where('aircraft_id', $flight->aircraft_id)->get();

        // Những ghế đã bị khóa trong Database (có vé nối với booking đang pending còn hạn hoặc đã paid)
        $lockedSeatIdsDb = \App\Models\Ticket::where('flight_id', $id)
            ->whereHas('booking', function ($query) {
                $query->where('status', 'paid')
                      ->orWhere(function ($q) {
                          $q->where('status', 'pending')
                            ->where(function ($subQ) {
                                $subQ->whereNull('expires_at')
                                     ->orWhere('expires_at', '>', now());
                            });
                      });
            })
            ->pluck('seat_id')
            ->toArray();

        $tripType = $request->query('trip_type', 'one_way');
        $strategy = \App\Services\Pricing\PricingStrategyFactory::resolve($tripType);
        
        // Thử lấy user từ Sanctum token (do endpoint này public nên auth() có thể null)
        if ($user = auth('sanctum')->user()) {
            if ($user->is_vip) {
                $strategy = new \App\Services\Pricing\VipPricingDecorator($strategy);
            }
        }
        
        $basePrice = $strategy->calculate((float) $flight->base_price);

        $mappedSeats = $seats->map(function ($seat) use ($lockedSeatIdsDb, $id, $basePrice) {
            // Kiểm tra xem có đang bị khóa trên RAM không
            $cacheKey = "lock_flight_{$id}_seat_{$seat->id}";
            $isLockedInCache = \Illuminate\Support\Facades\Cache::has($cacheKey);

            // Tính giá động theo hàng ghế
            preg_match('/(\d+)/', $seat->seat_number, $matches);
            $rowNum = isset($matches[1]) ? (int) $matches[1] : 0;
            
            $price = $basePrice;
            $seatClass = 'Tiêu chuẩn';

            if ($rowNum <= 5) {
                $price = $basePrice * 1.20;
                $seatClass = 'Thương gia';
            } elseif ($rowNum >= 15) {
                $price = $basePrice * 0.95;
                $seatClass = 'Tiết kiệm';
            }

            return [
                'id' => $seat->id,
                'seat_number' => $seat->seat_number,
                'seat_class' => $seatClass, // Ghi đè class trong DB
                'price' => $price, // Gửi giá thực tế về Frontend
                'is_locked' => in_array($seat->id, $lockedSeatIdsDb) || $isLockedInCache,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $mappedSeats,
        ]);
    }
}
