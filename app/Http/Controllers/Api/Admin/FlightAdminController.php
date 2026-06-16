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

        // SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('flight_number', 'like', "%$search%")
                  ->orWhere('status', 'like', "%$search%");
            });
        }

        // PAGINATION
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
        return Flight::create($request->all());
    }

    public function update(Request $request, $id)
    {
        $flight = Flight::findOrFail($id);
        $flight->update($request->all());

        return $flight;
    }

    public function destroy($id)
    {
        Flight::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }
}