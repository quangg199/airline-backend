<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Service;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * Tạo đơn đặt vé mới (Yêu cầu đăng nhập)
     * POST /api/bookings
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        // StoreBookingRequest has already validated and sanitized all inputs.
        // $validated contains ONLY the declared fields — no dirty data can pass through.
        $validated = $request->validated();

        $flight = Flight::with(['departureAirport', 'arrivalAirport'])
            ->findOrFail($validated['flight_id']);

        // Calculate ancillary services total (if any)
        $servicesTotal = 0;
        $serviceData   = [];

        if (!empty($validated['service_ids'])) {
            $services = Service::whereIn('id', $validated['service_ids'])->get();

            foreach ($services as $service) {
                $servicesTotal                   += $service->price;
                $serviceData[$service->id] = [
                    'quantity'          => 1,
                    'price_at_purchase' => $service->price,
                ];
            }
        }

        $totalAmount = $flight->base_price + $servicesTotal;

        // Create the Booking record (status = pending until payment confirmed)
        $booking = Booking::create([
            'user_id'      => $request->user()->id,
            'flight_id'    => $flight->id,
            'pnr_code'     => strtoupper(Str::random(6)),
            'total_amount' => $totalAmount,
            'status'       => 'pending',
        ]);

        // Create the passenger Ticket linked to this Booking
        Ticket::create([
            'booking_id'      => $booking->id,
            'flight_id'       => $flight->id,
            'ticket_code'     => strtoupper(Str::random(10)),
            'passenger_name'  => $validated['passenger_name'],
            'identity_number' => $validated['identity_number'],
            'seat_id'         => null, // Seat is assigned later via BookingObserver on payment
            'ticket_price'    => $flight->base_price,
        ]);

        // Attach ancillary services to the booking (pivot table: booking_service)
        if (!empty($serviceData)) {
            $booking->services()->attach($serviceData);
        }

        $booking->load(['flight.departureAirport', 'flight.arrivalAirport', 'tickets', 'services']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Đặt vé thành công! Vui lòng tiến hành thanh toán để xác nhận.',
            'data'    => $booking,
        ], 201);
    }

    /**
     * Xem chi tiết đơn đặt vé
     * GET /api/bookings/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // 1. Lấy Booking (chưa filter bằng user_id)
        $booking = Booking::with(['flight.departureAirport', 'flight.arrivalAirport', 'tickets', 'services'])
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
        $query = Booking::with(['flight.departureAirport', 'flight.arrivalAirport', 'tickets']);

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
}
