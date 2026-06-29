<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $repo
    ) {}

    public function getDashboardData()
    {
        return [
            'stats' => [
                'totalFlights' => $this->repo->getTotalFlights(),
                'totalBookings' => $this->repo->getTotalBookings(),
                'totalUsers' => $this->repo->getTotalUsers(),
                'revenue' => $this->repo->getRevenue(),
            ],

            'analytics' => $this->repo->getMonthlyBookings(),

            'recentBookings' => $this->repo->getRecentBookings(),
            'recentFlights' => $this->repo->getRecentFlights(),
        ];
    }
}