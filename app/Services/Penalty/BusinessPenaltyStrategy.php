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
        $oldFlight = $booking->flight;
        $ticketsCount = $booking->tickets->count() ?: 1;

        $oldBase = (float) $oldFlight->base_price;
        $newBase = (float) $newFlight->base_price;

        // Miễn phí đổi chuyến (0 VND phạt) nhưng vẫn phải trả chênh lệch giá vé nếu chuyến mới đắt hơn
        $diff = max(0, ($newBase - $oldBase) * $ticketsCount);
        return $diff;
    }
}
