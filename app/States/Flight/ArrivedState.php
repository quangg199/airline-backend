<?php

namespace App\States\Flight;

use Exception;

class ArrivedState extends FlightState
{
    public function transitionTo(string $newState): void
    {
        throw new Exception("Lỗi Quy Trình: Chuyến bay đã kết thúc ('arrived'), không thể thay đổi trạng thái nữa.");
    }

    public function getStatusString(): string
    {
        return 'arrived';
    }
}
