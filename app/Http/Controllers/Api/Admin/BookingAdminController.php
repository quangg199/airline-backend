<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

class BookingAdminController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(
        BookingService $bookingService
    ) {
        $this->bookingService = $bookingService;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->bookingService->getAllBookings()
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->bookingService->getBookingById($id)
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->bookingService->deleteBooking($id);

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully'
        ]);
    }
}