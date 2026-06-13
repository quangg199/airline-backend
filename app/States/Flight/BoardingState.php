<?php

namespace App\States\Flight;

use Exception;

class BoardingState extends FlightState
{
    public function transitionTo(string $newState): void
    {
        if (!in_array($newState, ['in_flight', 'delayed', 'cancelled'])) {
            throw new Exception("Lỗi Quy Trình: Chuyến bay đang 'Boarding', chỉ có thể cấp phép cất cánh ('in_flight').");
        }
        
        $this->flight->status = $newState;
        $this->flight->save();
    }

    public function getStatusString(): string
    {
        return 'boarding';
    }
}
