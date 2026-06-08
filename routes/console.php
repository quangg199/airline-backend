<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-Cancel unpaid bookings older than 5 minutes
\Illuminate\Support\Facades\Schedule::call(function () {
    $expiredBookings = \App\Models\Booking::where('status', 'pending')
        ->where('created_at', '<=', now()->subMinutes(5))
        ->get();
        
    foreach ($expiredBookings as $booking) {
        $booking->update(['status' => 'cancelled']);
    }
})->everyMinute();
