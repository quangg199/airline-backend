<?php

namespace App\Repositories\Interfaces;

interface DashboardRepositoryInterface
{
    public function getTotalUsers();
    public function getTotalFlights();
    public function getTotalBookings();
    public function getRevenue();
}