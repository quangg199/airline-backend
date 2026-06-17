<?php

namespace App\Providers;

use App\Services\CheckInService;
use Illuminate\Support\ServiceProvider;

/**
 * CheckInServiceProvider
 * 
 * Đăng ký CheckInService vào IoC container
 * để có thể sử dụng Facade CheckInFacade
 */
class CheckInServiceProvider extends ServiceProvider
{
    /**
     * Đăng ký các service vào container
     * 
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('check-in-service', function () {
            return new CheckInService();
        });
    }

    /**
     * Bootstrap các service
     * 
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
