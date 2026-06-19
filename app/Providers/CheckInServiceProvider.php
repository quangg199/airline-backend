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
        $this->app->singleton(\App\Contracts\CheckInServiceInterface::class, \App\Services\CheckInService::class);
        $this->app->alias(\App\Contracts\CheckInServiceInterface::class, 'check-in-service');
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
