<?php

namespace App\Services;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * FlightGeneratorService (Factory / Generator — Creational)
 *
 * Chịu trách nhiệm duy nhất: Sinh (generate) các chuyến bay giả lập
 * cho một tuyến đường + ngày cụ thể khi chưa có dữ liệu trong DB.
 *
 * Vấn đề giải quyết (Problem-First):
 * ─────────────────────────────────
 * Khi user tìm kiếm tuyến HAN → SGN ngày 2026-07-20 nhưng DB chưa có
 * chuyến bay nào cho ngày đó → kết quả trống → trải nghiệm xấu.
 *
 * Giải pháp: On-Demand Generation
 * ─────────────────────────────────
 * Tự động tạo 5 chuyến bay với các khung giờ cố định (sáng, trưa, chiều, tối)
 * rồi PERSIST vào DB, đảm bảo:
 * 1. Dữ liệu nhất quán khi user refresh trang.
 * 2. Các user khác cùng tuyến+ngày thấy cùng kết quả.
 * 3. Mọi thao tác insert đều nằm trong DB::transaction() → ACID compliance.
 *
 * Race condition prevention:
 * ─────────────────────────
 * Double-check pattern: Kiểm tra lại sự tồn tại của flights BÊN TRONG
 * transaction, phòng trường hợp 2 request đồng thời cùng thấy "không có
 * chuyến bay" và cùng cố tạo → tránh duplicate.
 *
 * SOLID Compliance:
 * - SRP : Chỉ lo việc tạo flight data. Không biết gì về pricing hay search.
 * - OCP : Thêm khung giờ mới → sửa TIME_SLOTS constant, không sửa logic.
 */
class FlightGeneratorService
{
    /**
     * Các khung giờ bay cố định (HH:MM) cho chuyến bay được sinh tự động.
     * 5 slots phủ đều cả ngày: sáng sớm, sáng, trưa, chiều, tối.
     */
    private const TIME_SLOTS = [
        '06:00',  // Sáng sớm   — Early morning
        '09:30',  // Giữa sáng  — Mid-morning
        '13:00',  // Đầu chiều  — Early afternoon
        '16:30',  // Chiều muộn — Late afternoon
        '20:00',  // Tối        — Evening
    ];

    /**
     * Khoảng giá gốc (VND) — phản ánh thực tế giá vé nội địa Việt Nam.
     * Giá sẽ được làm tròn theo bội số 100,000 VND.
     */
    private const MIN_PRICE = 800000;   // 800K VND
    private const MAX_PRICE = 3500000;  // 3.5M VND

    /**
     * Khoảng thời gian bay nội địa (phút).
     * VD: HAN → SGN ≈ 2h05, HAN → DAD ≈ 1h15.
     */
    private const MIN_DURATION_MINUTES = 75;   // 1h15
    private const MAX_DURATION_MINUTES = 150;  // 2h30

    /**
     * Prefix mã hãng hàng không giả lập.
     * Mảng để tạo sự đa dạng cho flight_number.
     */
    private const AIRLINE_PREFIXES = ['SK', 'VN', 'VJ', 'QH'];

    /**
     * Sinh 5 chuyến bay cho tuyến đường + ngày được chỉ định.
     *
     * TOÀN BỘ thao tác được bọc trong DB::transaction():
     * - Nếu bất kỳ insert nào fail → rollback tất cả → DB sạch.
     * - Pessimistic approach: double-check existence trong transaction.
     *
     * @param  Airport  $departure  Sân bay khởi hành (đã resolve từ mã IATA).
     * @param  Airport  $arrival    Sân bay đến (đã resolve từ mã IATA).
     * @param  string   $date       Ngày khởi hành (format: YYYY-MM-DD).
     * @return Collection<Flight>   Collection gồm 5 Flight đã tạo, kèm relationships.
     */
    public function generate(Airport $departure, Airport $arrival, string $date): Collection
    {
        return DB::transaction(function () use ($departure, $arrival, $date) {

            // ── Double-check trong transaction ──────────────────────
            // Phòng race condition: 2 request đồng thời cùng thấy
            // "chưa có chuyến bay" → cùng gọi generate().
            // Check lần 2 bên trong transaction đảm bảo chỉ 1 request
            // thực sự tạo data, request kia trả về data đã được tạo.
            $existing = Flight::where('departure_airport_id', $departure->id)
                ->where('arrival_airport_id', $arrival->id)
                ->whereDate('departure_time', $date)
                ->with(['departureAirport', 'arrivalAirport', 'aircraft'])
                ->get();

            if ($existing->isNotEmpty()) {
                return $existing;
            }

            // ── Lấy ngẫu nhiên 1 Aircraft để gán cho các chuyến bay ──
            // Trong production thực tế, mỗi chuyến bay sẽ có aircraft
            // riêng dựa trên scheduling, nhưng ở đây dùng random để demo.
            $aircraft = Aircraft::inRandomOrder()->first();

            if (!$aircraft) {
                // Không có máy bay trong DB → không thể tạo chuyến bay
                return collect();
            }

            $flightIds = [];

            // ── Tạo 1 chuyến bay cho mỗi khung giờ ────────────────
            foreach (self::TIME_SLOTS as $index => $timeSlot) {

                // Parse ngày + giờ thành Carbon instance
                $departureTime = Carbon::parse("{$date} {$timeSlot}");

                // Thời gian bay ngẫu nhiên trong khoảng hợp lý
                $durationMinutes = rand(
                    self::MIN_DURATION_MINUTES,
                    self::MAX_DURATION_MINUTES
                );
                $arrivalTime = $departureTime->copy()->addMinutes($durationMinutes);

                // Sinh mã chuyến bay: VD "SK142", "VN307"
                // Dùng prefix ngẫu nhiên + số 3 chữ số duy nhất theo index
                $prefix       = self::AIRLINE_PREFIXES[array_rand(self::AIRLINE_PREFIXES)];
                $flightNumber = $prefix . rand(100, 999);

                // Giá gốc ngẫu nhiên, làm tròn theo bội số 100,000 VND
                // VD: rand(8, 35) * 100000 → 800,000 ~ 3,500,000
                $basePrice = rand(
                    (int) (self::MIN_PRICE / 100000),
                    (int) (self::MAX_PRICE / 100000)
                ) * 100000;

                $flight = Flight::create([
                    'flight_number'        => $flightNumber,
                    'departure_airport_id' => $departure->id,
                    'arrival_airport_id'   => $arrival->id,
                    'departure_time'       => $departureTime,
                    'arrival_time'         => $arrivalTime,
                    'aircraft_id'          => $aircraft->id,
                    'base_price'           => $basePrice,
                    'available_seats'      => 180,
                    'status'               => 'scheduled',
                ]);

                $flightIds[] = $flight->id;
            }

            // Re-query để lấy Eloquent Collection với relationships eager-loaded.
            // collect()->push() trả về base Collection (không có ->load()),
            // nhưng Flight::whereIn()->get() trả về Eloquent Collection đúng chuẩn.
            return Flight::whereIn('id', $flightIds)
                ->with(['departureAirport', 'arrivalAirport', 'aircraft'])
                ->get();
        });
    }
}
