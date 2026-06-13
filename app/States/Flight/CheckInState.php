<?php

namespace App\States\Flight;

use Exception;

class CheckInState extends FlightState
{
    public function transitionTo(string $newState): void
    {
        if (!in_array($newState, ['boarding', 'delayed', 'cancelled'])) {
            throw new Exception("Lỗi Quy Trình: Chuyến bay đang làm thủ tục 'Check-In', tiếp theo phải là 'Boarding'. Cấm nhảy cóc trạng thái.");
        }
        
        $this->flight->status = $newState;
        $this->flight->save();
    }

    public function getStatusString(): string
    {
        return 'check_in';
    }
}
