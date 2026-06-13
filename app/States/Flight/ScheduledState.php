<?php

namespace App\States\Flight;

use Exception;

class ScheduledState extends FlightState
{
    public function transitionTo(string $newState): void
    {
        if (!in_array($newState, ['check_in', 'delayed', 'cancelled'])) {
            throw new Exception("Lỗi Quy Trình: Chuyến bay đang ở trạng thái 'Đã lên lịch' chỉ có thể chuyển sang 'Check-In', 'Delayed' hoặc 'Cancelled'.");
        }
        
        $this->flight->status = $newState;
        $this->flight->save();
    }

    public function getStatusString(): string
    {
        return 'scheduled';
    }
}
