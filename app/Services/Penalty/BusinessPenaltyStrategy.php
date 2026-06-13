<?php

namespace App\Services\Penalty;

use App\Models\Booking;
use App\Models\Flight;

class BusinessPenaltyStrategy implements PenaltyStrategyInterface
{
    /** Business: cancel free if before 24h, reschedule free */
    public function canCancel(Booking $booking): bool
    {
        $dep = $booking->flight->departure_time;
        return now()->diffInHours($dep, false) > 24 || now()->lt($booking->flight->departure_time->subHours(24));
    }

    public function cancelRefundAmount(Booking $booking): float
    {
        // Fully refundable when allowed
        return (float) $booking->total_amount;
    }

    public function rescheduleFee(Booking $booking, Flight $newFlight): float
    {
        return 0.0;
    }
}
