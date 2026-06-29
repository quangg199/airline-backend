<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\User;
use Carbon\Carbon;

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
        return Booking::whereIn('status', ['paid', 'PAID', 'success', 'completed'])
            ->sum('total_amount');
    }

    public function getMonthlyBookings()
    {
        $months = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            $months[] = $date->format('M');

            $data[] = Booking::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
        }

        return [
            'months' => $months,
            'bookings' => $data,
        ];
    }

    public function getRecentBookings()
{
    return Booking::with([
        'user:id,name,email',
        'flight:id,flight_number'
    ])
    ->latest()
    ->take(5)
    ->get();
}

    public function getRecentFlights()
        {
            return Flight::with([
                'departureAirport:id,city,name,code',
                'arrivalAirport:id,city,name,code'
            ])
            ->latest()
            ->take(5)
            ->get();
        }
}