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

    public function validateCheckIn(): void
    {
        $now = now();
        $departure = \Carbon\Carbon::parse($this->flight->departure_time);

        $checkInOpen = $departure->copy()->subHours(24);
        $checkInClose = $departure->copy()->subHours(2);

        if ($now->lt($checkInOpen)) {
            throw new Exception(
                "Check-in chưa mở. Mở vào: " . $checkInOpen->format('Y-m-d H:i')
            );
        }

        if ($now->gt($checkInClose)) {
            throw new Exception("Check-in đã đóng (trước giờ bay 2 tiếng).");
        }

        // Hợp lệ, chuyển trạng thái chuyến bay sang 'check_in'
        $this->flight->transitionTo('check_in');
    }
}
