<?php

namespace App\States\Flight;

use Exception;

class InFlightState extends FlightState
{
    public function transitionTo(string $newState): void
    {
        if ($newState !== 'arrived') {
            throw new Exception("Lỗi Quy Trình: Máy bay đang bay trên không ('in_flight'), chỉ có thể hạ cánh ('arrived'). Không thể delay hay cancel.");
        }
        
        $this->flight->status = $newState;
        $this->flight->save();
    }

    public function getStatusString(): string
    {
        return 'in_flight';
    }
}
