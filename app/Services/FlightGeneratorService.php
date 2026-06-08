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
 * Vấn đề giải quyết (Problem-First):
 * ─────────────────────────────────
 * Yêu cầu business: Mỗi lần tìm kiếm cho 1 tuyến + ngày phải trả về
 * ĐÚNG 15 chuyến bay: 3 hãng × 5 khung giờ, mã số cố định, nhất quán.
 *
 * Giải pháp: Deterministic Generation
 * ─────────────────────────────────────
 * Thay vì random prefix + random number, ta dùng bảng cố định AIRLINE_SCHEDULES
 * chứa đầy đủ thông tin từng hãng: tên, prefix, 5 số hiệu chuyến bay, 5 khung giờ.
 * → Cùng tuyến + ngày luôn cho ra 15 chuyến bay giống nhau (idempotent).
 *
 * SOLID Compliance:
 * - SRP : Chỉ lo việc tạo flight data.
 * - OCP : Thêm hãng mới → thêm entry vào AIRLINE_SCHEDULES, không sửa logic.
 */
class FlightGeneratorService
{
    /**
     * Bảng lịch bay cố định theo hãng.
     * Mỗi hãng có:
     *  - name      : Tên hiển thị đầy đủ
     *  - prefix    : Prefix mã hiệu chuyến bay (để FlightCard nhận diện màu sắc)
     *  - baseNumbers : 5 số hiệu gốc (sẽ được gắn suffix ngày để tránh unique constraint)
     *  - slots     : 5 khung giờ tương ứng theo thứ tự [HH:MM]
     *  - duration  : Thời gian bay (phút) — cố định theo hãng
     *  - prices    : Giá gốc tương ứng (VND, làm tròn 100K)
     *
     * Lý do dùng baseNumbers + suffix ngày:
     *   flight_number là UNIQUE trong toàn bảng flights.
     *   Cùng hãng, cùng số hiệu nhưng khác ngày/tuyến → cần key khác nhau.
     *   VD: VN201-0720 (20/07) ≠ VN201-0715 (15/07)
     */
    private const AIRLINE_SCHEDULES = [
        [
            'name'        => 'Vietnam Airlines',
            'prefix'      => 'VN',
            'baseNumbers' => ['201', '202', '203', '204', '205'],
            'slots'       => ['06:00', '09:00', '12:30', '15:45', '19:00'],
            'duration'    => 115, // 1h55
            'prices'      => [1200000, 1500000, 1300000, 1400000, 1100000],
            'aircraft'    => ['model' => 'Boeing 787', 'tail' => 'VN-B787']
        ],
        [
            'name'        => 'VietJet Air',
            'prefix'      => 'VJ',
            'baseNumbers' => ['301', '302', '303', '304', '305'],
            'slots'       => ['07:00', '10:30', '13:00', '16:00', '20:00'],
            'duration'    => 120, // 2h00
            'prices'      => [900000, 1100000, 950000, 1000000, 800000],
            'aircraft'    => ['model' => 'Airbus A320neo', 'tail' => 'VJ-A320']
        ],
        [
            'name'        => 'FlightBus',
            'prefix'      => 'FB',
            'baseNumbers' => ['401', '402', '403', '404', '405'],
            'slots'       => ['05:30', '08:30', '11:30', '14:00', '17:30'],
            'duration'    => 125, // 2h05
            'prices'      => [700000, 850000, 750000, 800000, 650000],
            'aircraft'    => ['model' => 'Embraer 190', 'tail' => 'FB-E190']
        ],
    ];

    /**
     * Sinh đúng 15 chuyến bay cho tuyến đường + ngày được chỉ định.
     *
     * Idempotent: Gọi nhiều lần với cùng params → cùng kết quả.
     * Double-check: Kiểm tra bên trong transaction để tránh race condition.
     *
     * @param  Airport  $departure  Sân bay khởi hành.
     * @param  Airport  $arrival    Sân bay đến.
     * @param  string   $date       Ngày khởi hành (YYYY-MM-DD).
     * @return Collection<Flight>   15 Flight đã tạo kèm relationships.
     */
    public function generate(Airport $departure, Airport $arrival, string $date): Collection
    {
        return DB::transaction(function () use ($departure, $arrival, $date) {

            // Double-check trong transaction — tránh race condition
            $existing = Flight::where('departure_airport_id', $departure->id)
                ->where('arrival_airport_id', $arrival->id)
                ->whereDate('departure_time', $date)
                ->with(['departureAirport', 'arrivalAirport', 'aircraft'])
                ->get();

            if ($existing->isNotEmpty()) {
                return $existing;
            }

            // Lấy hoặc tạo Aircraft riêng cho mỗi hãng
            $aircrafts = [];
            foreach (self::AIRLINE_SCHEDULES as $airline) {
                $aircrafts[$airline['prefix']] = Aircraft::firstOrCreate(
                    ['tail_number' => $airline['aircraft']['tail']],
                    ['model' => $airline['aircraft']['model'], 'status' => 1]
                );
            }

            // Suffix ngày: MMDD — đảm bảo flight_number global unique
            // VD: VN201-0715 (ngày 15/07), VN201-0720 (ngày 20/07)
            $dateSuffix = Carbon::parse($date)->format('md');

            $flightIds = [];

            // Sinh 3 hãng × 5 chuyến = 15 flights
            foreach (self::AIRLINE_SCHEDULES as $airline) {
                foreach ($airline['baseNumbers'] as $idx => $baseNum) {
                    // Mã chuyến bay: PREFIX + baseNum + "-" + MMDD
                    // VD: VN201-0715, VJ301-0715, FB401-0715
                    $flightNumber  = $airline['prefix'] . $baseNum . '-' . $dateSuffix;
                    $timeSlot      = $airline['slots'][$idx];
                    $departureTime = Carbon::parse("{$date} {$timeSlot}");
                    $arrivalTime   = $departureTime->copy()->addMinutes($airline['duration']);

                    // Kiểm tra không trùng flight_number (unique constraint)
                    $alreadyExists = Flight::where('flight_number', $flightNumber)
                        ->whereDate('departure_time', $date)
                        ->exists();

                    if ($alreadyExists) {
                        // Lấy ID của flight đã tồn tại để include vào kết quả
                        $existingId = Flight::where('flight_number', $flightNumber)
                            ->whereDate('departure_time', $date)
                            ->value('id');
                        if ($existingId) {
                            $flightIds[] = $existingId;
                        }
                        continue;
                    }

                    $flight = Flight::create([
                        'flight_number'        => $flightNumber,
                        'departure_airport_id' => $departure->id,
                        'arrival_airport_id'   => $arrival->id,
                        'departure_time'       => $departureTime,
                        'arrival_time'         => $arrivalTime,
                        'aircraft_id'          => $aircrafts[$airline['prefix']]->id,
                        // Giá random từ 500,000đ đến 1,500,000đ (bước giá 10k)
                        'base_price'           => rand(50, 150) * 10000,
                        'available_seats'      => 180,
                        'status'               => 'scheduled',
                    ]);

                    $flightIds[] = $flight->id;
                }
            }

            // Re-query với Eager Loading đầy đủ
            return Flight::whereIn('id', $flightIds)
                ->with(['departureAirport', 'arrivalAirport', 'aircraft'])
                ->orderBy('departure_time')
                ->get();
        });
    }
}
