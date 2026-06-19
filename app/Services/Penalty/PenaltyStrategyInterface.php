<?php

namespace App\Services\Penalty;

use App\Models\Booking;
use App\Models\Flight;

interface PenaltyStrategyInterface
{
    /**
     * Whether this fare class allows cancellation at the current time
     */
    public function canCancel(Booking $booking): bool;

    /**
     * Refund amount when cancelling (if allowed)
     */
    public function cancelRefundAmount(Booking $booking): float;

    /**
     * Fee to reschedule from booking to new flight
     */
    public function rescheduleFee(Booking $booking, Flight $newFlight): float;
}
