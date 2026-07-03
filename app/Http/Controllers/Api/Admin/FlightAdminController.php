<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use Illuminate\Http\Request;

class FlightAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Flight::with(['departureAirport', 'arrivalAirport', 'aircraft']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('flight_number', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('departure_time', $request->date);
        }

        $flights = $query->orderByDesc('id')->paginate(10);

        return response()->json([
            'data' => $flights->items(),
            'meta' => [
                'current_page' => $flights->currentPage(),
                'per_page'     => $flights->perPage(),
                'total'        => $flights->total(),
                'last_page'    => $flights->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'flight_number' => 'required',
            'departure_airport_id' => 'required',
            'arrival_airport_id' => 'required',
            'departure_time' => 'required',
            'arrival_time' => 'required',
            'aircraft_id' => 'required',
            'base_price' => 'required|numeric',
            'available_seats' => 'required|integer',
            'status' => 'required',
        ]);

        $flight = Flight::create($data);

        return response()->json([
            'message' => 'Created successfully',
            'data' => $flight
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $flight = Flight::findOrFail($id);

        $data = $request->validate([
            'flight_number' => 'required',
            'departure_airport_id' => 'required',
            'arrival_airport_id' => 'required',
            'departure_time' => 'required',
            'arrival_time' => 'required',
            'aircraft_id' => 'required',
            'base_price' => 'required|numeric',
            'available_seats' => 'required|integer',
            'status' => 'required',
        ]);

        $flight->update($data);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $flight
        ]);
    }

    public function destroy($id)
    {
        Flight::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}