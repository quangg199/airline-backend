<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function index(Request $request)
    {
        // Lấy danh sách chuyến bay kèm theo thông tin Sân bay và Máy bay
        // Eager Loading để tránh lỗi N+1 query
        $query = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft']);

        // Lọc theo Sân bay đi (nếu có)
        if ($request->has('from')) {
            $query->whereHas('departureAirport', function($q) use ($request) {
                $q->where('code', $request->from);
            });
        }

        // Lọc theo Sân bay đến (nếu có)
        if ($request->has('to')) {
            $query->whereHas('arrivalAirport', function($q) use ($request) {
                $q->where('code', $request->to);
            });
        }

        $flights = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'results' => $flights->count(),
            'data' => $flights
        ], 200);
    }
}
