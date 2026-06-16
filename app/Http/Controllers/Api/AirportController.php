<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AirportService;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    protected $airportService;

    public function __construct(AirportService $airportService)
    {
        $this->airportService = $airportService;
    }

    /**
     * GET /api/airports
     */
    public function index()
    {
        return response()->json(
            $this->airportService->getAllAirports()
        );
    }

    /**
     * POST /api/admin/airports
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|max:10|unique:airports,code',
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
        ]);

        $airport = $this->airportService->createAirport($validated);

        return response()->json([
            'message' => 'Airport created successfully',
            'data' => $airport
        ], 201);
    }

    /**
     * PUT /api/admin/airports/{id}
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'code' => 'required|max:10|unique:airports,code,' . $id,
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
        ]);

        $airport = $this->airportService->updateAirport($id, $validated);

        return response()->json([
            'message' => 'Airport updated successfully',
            'data' => $airport
        ]);
    }

    /**
     * DELETE /api/admin/airports/{id}
     */
    public function destroy($id)
    {
        $this->airportService->deleteAirport($id);

        return response()->json([
            'message' => 'Airport deleted successfully'
        ]);
    }
}