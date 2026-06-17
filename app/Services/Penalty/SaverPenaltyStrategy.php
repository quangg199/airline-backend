<?php

namespace App\Services\Penalty;

use App\Models\Booking;
use App\Models\Flight;

class SaverPenaltyStrategy implements PenaltyStrategyInterface
{
    /** Saver tickets: cancel prohibited; reschedule fee = 500k + fare difference */
    public function canCancel(Booking $booking): bool
    {
        return false;
    }

    public function cancelRefundAmount(Booking $booking): float
    {
        return 0.0;
    }

    public function rescheduleFee(Booking $booking, Flight $newFlight): float
    {
        $oldFlight = $booking->flight;
        $ticketsCount = $booking->tickets->count() ?: 1;

        $oldBase = (float) $oldFlight->base_price;
        $newBase = (float) $newFlight->base_price;

        $diff = max(0, ($newBase - $oldBase) * $ticketsCount);
        return 500000 + $diff;
    }
}
