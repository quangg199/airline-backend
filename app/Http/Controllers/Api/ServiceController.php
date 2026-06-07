<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;

class ServiceController extends Controller
{
    /**
     * Lấy tất cả dịch vụ bổ sung từ DB
     */
    public function index()
    {
        $services = Service::all();

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }
}
