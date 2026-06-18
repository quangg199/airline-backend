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

    public function validateCheckIn(): void
    {
        $now = now();
        $departure = \Carbon\Carbon::parse($this->flight->departure_time);
        $checkInClose = $departure->copy()->subHours(2);

        if ($now->gt($checkInClose)) {
            throw new Exception("Check-in đã đóng (trước giờ bay 2 tiếng).");
        }

        // Chuyến bay đã ở trạng thái check_in và thời gian vẫn hợp lệ, cho phép check-in.
    }
}
