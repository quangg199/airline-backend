<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\BookingObserver;
use App\Models\Booking;

use App\Repositories\AirportRepository;
use App\Repositories\Interfaces\AirportRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AirportRepositoryInterface::class,
            AirportRepository::class
        );
    }

    public function boot(): void
    {
        Booking::observe(BookingObserver::class);
    }
}