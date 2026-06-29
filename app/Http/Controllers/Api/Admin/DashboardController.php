<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $service)
    {
        return response()->json(
            $service->getDashboardData()
        );
    }
}