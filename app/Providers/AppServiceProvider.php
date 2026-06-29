<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Booking;
use App\Observers\BookingObserver;

use App\Repositories\AirportRepository;
use App\Repositories\Interfaces\AirportRepositoryInterface;

use App\Repositories\BookingRepository;
use App\Repositories\BookingRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AirportRepositoryInterface::class,
            AirportRepository::class
        );

        $this->app->bind(
            BookingRepositoryInterface::class,
            BookingRepository::class
        );
    }

    public function boot(): void
    {
        Booking::observe(BookingObserver::class);
    }
}