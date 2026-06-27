<?php

namespace App\Repositories;

use App\Models\Flight;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardRepository
{
    public function getTotalFlights()
    {
        return Flight::count();
    }

    public function getTotalBookings()
    {
        return Booking::count();
    }

    public function getTotalUsers()
    {
        return User::count();
    }

    public function getRevenue()
    {
        return Booking::sum('amount'); // giả sử có cột amount
    }

    public function getRecentBookings($limit = 5)
    {
        return Booking::with(['flight', 'user'])
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getRecentFlights($limit = 5)
    {
        return Flight::latest()
            ->take($limit)
            ->get();
    }

    public function getFlightStatusSummary()
    {
        return Flight::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    public function getMonthlyBookingStats()
    {
        return Booking::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }
}