<?php

namespace App\States\Flight;

use Exception;

class CancelledState extends FlightState
{
    public function transitionTo(string $newState): void
    {
        throw new Exception("Lỗi Quy Trình: Chuyến bay đã bị hủy ('cancelled'), không thể khôi phục trạng thái.");
    }

    public function getStatusString(): string
    {
        return 'cancelled';
    }
}
