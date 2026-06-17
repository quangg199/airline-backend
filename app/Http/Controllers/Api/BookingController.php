<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\Penalty\PenaltyStrategyFactory;

class BookingController extends Controller
{
    /**
     * Tự động hủy các vé chờ thanh toán quá 5 phút.
     * Gọi hàm này trước khi query danh sách để đảm bảo data luôn mới
     * mà không cần chạy Cronjob ngầm.
     */
    private function autoCancelExpiredBookings(): void
    {
        Booking::where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'cancelled']);
    }

    /**
     * Tạo đơn đặt vé mới (Yêu cầu đăng nhập)
     * POST /api/bookings
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flight_id' => 'required|integer|exists:flights,id',
            'return_flight_id' => 'nullable|integer|exists:flights,id',
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'required|string|max:255',
            'passengers.*.identity_number' => 'required|string|max:50',
            'passengers.*.outbound_seat_id' => 'required|integer|exists:seats,id',
            'passengers.*.return_seat_id' => 'nullable|integer|exists:seats,id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id'
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $createdBookings = [];
            $servicesTotal = 0;
            $serviceData = [];
            
            if (!empty($validated['service_ids'])) {
                $services = Service::whereIn('id', $validated['service_ids'])->get();
                foreach ($services as $service) {
                    $servicesTotal += $service->price;
                    $serviceData[$service->id] = ['quantity' => 1, 'price_at_purchase' => $service->price];
                }
            }

            $userId = $request->user()->id;
            $tripType = !empty($validated['return_flight_id']) ? 'round_trip' : 'one_way';

            $createBookingForFlight = function ($flightId, $isReturn) use ($validated, $userId, $serviceData, $servicesTotal, $request, $tripType) {
                $flight = Flight::findOrFail($flightId);
                
                // Lấy thời gian hết hạn từ ghế đầu tiên
                $expiresAt = now()->addMinutes(15);
                foreach ($validated['passengers'] as $passenger) {
                    $seatId = $isReturn ? $passenger['return_seat_id'] : $passenger['outbound_seat_id'];
                    if ($seatId) {
                        $lockData = Cache::get("lock_flight_{$flightId}_seat_{$seatId}");
                        if (is_array($lockData) && isset($lockData['expires_at'])) {
                            $expiresAt = $lockData['expires_at'];
                            break;
                        }
                    }
                }

                $booking = Booking::create([
                    'user_id' => $userId,
                    'flight_id' => $flightId,
                    'pnr_code' => strtoupper(Str::random(6)),
                    'total_amount' => 0, // Will sum up later
                    'status' => 'pending',
                    'expires_at' => $expiresAt
                ]);

                $totalTicketAmount = 0;

                foreach ($validated['passengers'] as $passenger) {
                    $seatId = $isReturn ? $passenger['return_seat_id'] : $passenger['outbound_seat_id'];
                    if (!$seatId) continue;

                    $cacheKey = "lock_flight_{$flightId}_seat_{$seatId}";
                    $lockData = Cache::get($cacheKey);
                    $lockerId = is_array($lockData) ? $lockData['user_id'] : $lockData;
                    
                    if ($lockerId !== $userId) {
                        abort(400, "Ghế của chuyến bay đã hết thời gian giữ chỗ hoặc bạn chưa khóa ghế. Vui lòng chọn lại ghế.");
                    }

                    $seat = \App\Models\Seat::findOrFail($seatId);
                    preg_match('/(\d+)/', $seat->seat_number, $matches);
                    $rowNum = isset($matches[1]) ? (int) $matches[1] : 0;
                    
                    // Tính giá hiển thị dựa trên strategy (bao gồm VIP discount)
                    $strategy = \App\Services\Pricing\PricingStrategyFactory::resolve($tripType);
                    if ($request->user() && $request->user()->is_vip) {
                        $strategy = new \App\Services\Pricing\VipPricingDecorator($strategy);
                    }
                    $basePrice = $strategy->calculate((float) $flight->base_price);

                    $ticketPrice = $basePrice;
                    if ($rowNum <= 5) {
                        $ticketPrice = $basePrice * 1.20;
                    } elseif ($rowNum >= 15) {
                        $ticketPrice = $basePrice * 0.95;
                    }

                    $taxAmount = $ticketPrice * 0.80;
                    $totalTicketAmount += ($ticketPrice + $taxAmount);

                    Ticket::create([
                        'booking_id' => $booking->id,
                        'flight_id' => $flightId,
                        'ticket_code' => strtoupper(Str::random(10)),
                        'passenger_name' => $passenger['name'],
                        'identity_number' => $passenger['identity_number'],
                        'seat_id' => $seatId,
                        'ticket_price' => $ticketPrice,
                    ]);

                    Cache::forget($cacheKey);
                }

                $booking->total_amount = $totalTicketAmount + $servicesTotal;
                $booking->save();

                if (!empty($serviceData)) {
                    $booking->services()->attach($serviceData);
                }

                $booking->load(['flight.departureAirport', 'flight.arrivalAirport', 'tickets.seat']);
                return $booking;
            };

            $createdBookings[] = $createBookingForFlight($validated['flight_id'], false);

            if (!empty($validated['return_flight_id'])) {
                $createdBookings[] = $createBookingForFlight($validated['return_flight_id'], true);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Đặt vé thành công. Vui lòng thanh toán.',
                'data' => count($createdBookings) === 1 ? $createdBookings[0] : $createdBookings,
            ]);
        });
    }

    /**
     * KHÓA GHẾ TẠM THỜI (5 phút) BẰNG CACHE
     * POST /api/bookings/lock-seat
     */
    public function lockSeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flight_id' => 'required|integer|exists:flights,id',
            'outbound_seat_ids' => 'required|array|min:1',
            'outbound_seat_ids.*' => 'integer|exists:seats,id',
            'return_flight_id' => 'nullable|integer|exists:flights,id',
            'return_seat_ids' => 'nullable|array',
            'return_seat_ids.*' => 'integer|exists:seats,id',
        ]);

        $userId = $request->user()->id;

        $lockSeatProcess = function ($flightId, $seatIds) use ($userId) {
            foreach ($seatIds as $seatId) {
                $cacheKey = "lock_flight_{$flightId}_seat_{$seatId}";
                
                if (Cache::has($cacheKey)) {
                    $lockData = Cache::get($cacheKey);
                    $lockerId = is_array($lockData) ? $lockData['user_id'] : $lockData;
                    if ($lockerId !== $userId) {
                        abort(400, "Một trong các ghế đã có người khác chọn.");
                    }
                }

                $isBought = Ticket::where('flight_id', $flightId)
                    ->where('seat_id', $seatId)
                    ->whereHas('booking', function ($q) {
                        $q->whereIn('status', ['pending', 'paid']);
                    })->exists();
                    
                if ($isBought) {
                    abort(400, "Một trong các ghế đã được bán.");
                }

                Cache::put($cacheKey, [
                    'user_id' => $userId,
                    'expires_at' => now()->addMinutes(15)
                ], now()->addMinutes(15));
            }
        };

        $lockSeatProcess($validated['flight_id'], $validated['outbound_seat_ids']);
        
        if (!empty($validated['return_flight_id']) && !empty($validated['return_seat_ids'])) {
            $lockSeatProcess($validated['return_flight_id'], $validated['return_seat_ids']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Đã khóa ghế trong 15 phút.',
        ]);
    }

    /**
     * Xem chi tiết đơn đặt vé
     * GET /api/bookings/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->autoCancelExpiredBookings();

        // 1. Lấy Booking (chưa filter bằng user_id)
        $booking = Booking::with(['flight.departureAirport', 'flight.arrivalAirport', 'tickets.seat', 'services'])
            ->findOrFail($id);

        // 2. Resource-level Authorization: Dùng BookingPolicy (Protection Proxy)
        // Sẽ throw 403 nếu user không phải chủ booking hoặc admin
        \Illuminate\Support\Facades\Gate::authorize('view', $booking);

        return response()->json([
            'status' => 'success',
            'data'   => $booking,
        ]);
    }

    /**
     * Lấy danh sách tất cả đơn đặt vé của user hiện tại
     * GET /api/bookings
     */
    public function index(Request $request): JsonResponse
    {
        $this->autoCancelExpiredBookings();

        $query = Booking::with(['flight.departureAirport', 'flight.arrivalAirport', 'tickets.seat']);

        // Nếu KHÔNG phải Admin -> Chỉ lấy booking của chính user đó
        if (!$request->user()->roles()->where('name', 'admin')->exists()) {
            $query->where('user_id', $request->user()->id);
        }

        $bookings = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data'   => $bookings,
        ]);
    }

    /**
     * Hủy vé (self-service)
     * POST /api/bookings/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = Booking::with(['flight', 'tickets.seat'])->findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('view', $booking);

        $strategy = PenaltyStrategyFactory::resolve($booking);
        if (! $strategy->canCancel($booking)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Loại vé này không được phép hủy.',
            ], 422);
        }

        $refund = $strategy->cancelRefundAmount($booking);

        // Mark booking cancelled and persist (business-side refund handling is out-of-scope)
        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Đã hủy vé. Số tiền hoàn trả: ' . number_format($refund, 0, ',', '.') . ' VND',
            'refund_amount' => $refund,
        ]);
    }

    /**
     * Tính phí đổi chuyến (không thực hiện đổi tự động tại endpoint này)
     * POST /api/bookings/{id}/reschedule
     * body: { new_flight_id }
     */
    public function reschedule(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_flight_id' => 'required|integer|exists:flights,id',
        ]);

        $booking = Booking::with(['flight', 'tickets.seat'])->findOrFail($id);
        \Illuminate\Support\Facades\Gate::authorize('view', $booking);

        $newFlight = Flight::findOrFail($validated['new_flight_id']);

        if (now()->gt($booking->flight->departure_time)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể đổi chuyến sau thời gian khởi hành.',
            ], 422);
        }

        $strategy = PenaltyStrategyFactory::resolve($booking);
        $fee = $strategy->rescheduleFee($booking, $newFlight);

        return response()->json([
            'status' => 'success',
            'message' => 'Tính phí đổi chuyến thành công.',
            'data' => [
                'reschedule_fee' => $fee,
                'original_amount' => $booking->total_amount,
            ],
        ]);
    }

    /**
     * Hoàn thành đổi vé sau khi thanh toán
     * PUT /api/bookings/{id}/confirm-reschedule
     * body: { new_flight_id, new_seat_ids? }
     */
    public function confirmReschedule(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'new_flight_id' => 'required|integer|exists:flights,id',
            'new_seat_ids' => 'nullable|array',
            // allow nullable entries so frontend can send placeholder nulls
            'new_seat_ids.*' => 'nullable|integer|exists:seats,id',
        ]);

        return DB::transaction(function () use ($validated, $request, $id) {
            $booking = Booking::with(['flight', 'tickets.seat'])->findOrFail($id);
            \Illuminate\Support\Facades\Gate::authorize('view', $booking);

            $newFlight = Flight::findOrFail($validated['new_flight_id']);

            // Kiểm tra không thể đổi sau khi bay
            if (now()->gt($booking->flight->departure_time)) {
                abort(422, 'Không thể đổi chuyến sau thời gian khởi hành.');
            }

            // Kiểm tra loại vé có được phép đổi không
            $strategy = PenaltyStrategyFactory::resolve($booking);

            // Giải phóng ghế cũ
            foreach ($booking->tickets as $ticket) {
                $ticket->seat_id = null;
                $ticket->save();
            }

            // Gán ghế mới hoặc tự động tìm ghế có sẵn
            $newSeatIds = $validated['new_seat_ids'] ?? [];
            foreach ($booking->tickets as $index => $ticket) {
                $newSeatId = $newSeatIds[$index] ?? null;

                // Nếu không cung cấp seat_id, tự động tìm ghế trống
                if (!$newSeatId) {
                    $oldSeat = $ticket->seat;
                    $preferredClass = $oldSeat ? $oldSeat->seat_class : 'economy';
                    
                    // Tìm ghế trống cùng class hoặc ghế khác nếu không đủ
                    $availableSeat = Seat::where('aircraft_id', $newFlight->aircraft_id)
                        ->where('seat_class', $preferredClass)
                        ->whereNotIn('id', Ticket::where('flight_id', $newFlight->id)
                            ->whereHas('booking', function ($q) {
                                $q->whereIn('status', ['pending', 'paid']);
                            })->pluck('seat_id'))
                        ->first();

                    if (!$availableSeat) {
                        // Tìm ghế trống bất kỳ
                        $availableSeat = Seat::where('aircraft_id', $newFlight->aircraft_id)
                            ->whereNotIn('id', Ticket::where('flight_id', $newFlight->id)
                                ->whereHas('booking', function ($q) {
                                    $q->whereIn('status', ['pending', 'paid']);
                                })->pluck('seat_id'))
                            ->first();
                    }

                    if (!$availableSeat) {
                        abort(422, 'Không có ghế trống trên chuyến bay mới.');
                    }

                    $newSeatId = $availableSeat->id;
                }

                $ticket->seat_id = $newSeatId;
                $ticket->flight_id = $newFlight->id;
                $ticket->save();
            }

            // Cập nhật booking với chuyến bay mới
            $booking->flight_id = $newFlight->id;
            $booking->save();

            $booking->load(['flight.departureAirport', 'flight.arrivalAirport', 'tickets.seat']);

            return response()->json([
                'status' => 'success',
                'message' => 'Đã đổi chuyến bay thành công.',
                'data' => $booking,
            ]);
        });
    }
}
