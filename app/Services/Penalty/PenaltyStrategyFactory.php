<?php

namespace App\Services\Penalty;

use App\Models\Booking;

class PenaltyStrategyFactory
{
    public static function resolve(Booking $booking): PenaltyStrategyInterface
    {
        // Determine fare class from first ticket's seat
        $firstTicket = $booking->tickets->first();
        $seatClass = $firstTicket && $firstTicket->seat ? strtolower($firstTicket->seat->seat_class) : 'economy';

        if (in_array($seatClass, ['business', 'b', 'thương gia', '2'])) {
            return new BusinessPenaltyStrategy();
        }

        return new SaverPenaltyStrategy();
    }
}
