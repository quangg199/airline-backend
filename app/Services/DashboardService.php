<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    protected $repo;

    public function __construct(DashboardRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getDashboardData()
    {
        $status = $this->repo->getFlightStatusSummary();

        return [
            'stats' => [
                'totalFlights' => $this->repo->getTotalFlights(),
                'totalBookings' => $this->repo->getTotalBookings(),
                'totalUsers' => $this->repo->getTotalUsers(),
                'revenue' => $this->repo->getRevenue(),
            ],

            'recentBookings' => $this->repo->getRecentBookings(),
            'recentFlights' => $this->repo->getRecentFlights(),

            'flightStatus' => [
                'Scheduled' => $status['Scheduled'] ?? 0,
                'Delayed' => $status['Delayed'] ?? 0,
                'Cancelled' => $status['Cancelled'] ?? 0,
            ],

            'monthlyStats' => $this->repo->getMonthlyBookingStats(),
        ];
    }
}