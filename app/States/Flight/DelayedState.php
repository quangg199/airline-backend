<?php

namespace App\States\Flight;

use Exception;

class DelayedState extends FlightState
{
    public function transitionTo(string $newState): void
    {
        // Khi hết delay, chuyến bay thường quay lại scheduled hoặc check_in
        if (!in_array($newState, ['scheduled', 'check_in', 'cancelled'])) {
            throw new Exception("Lỗi Quy Trình: Chuyến bay đang bị hoãn ('delayed'), chỉ có thể quay về 'scheduled', 'check_in' hoặc bị 'cancelled'.");
        }
        
        $this->flight->status = $newState;
        $this->flight->save();
    }

    public function getStatusString(): string
    {
        return 'delayed';
    }
}
