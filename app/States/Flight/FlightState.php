<?php

namespace App\States\Flight;

use App\Models\Flight;

abstract class FlightState
{
    protected Flight $flight;

    public function __construct(Flight $flight)
    {
        $this->flight = $flight;
    }

    /**
     * Chuyển đổi trạng thái. Sẽ throw exception nếu không hợp lệ.
     * @param string $newState
     * @return void
     * @throws \Exception
     */
    abstract public function transitionTo(string $newState): void;

    /**
     * Lấy tên trạng thái hiện tại (string).
     * @return string
     */
    abstract public function getStatusString(): string;

    /**
     * Xác nhận xem chuyến bay có đủ điều kiện để thực hiện Check-In không.
     * Mặc định ném Exception vì hầu hết các trạng thái (Boarding, InFlight, Arrived, Cancelled) đều không cho phép check-in.
     * 
     * @throws \Exception
     */
    public function validateCheckIn(): void
    {
        throw new \Exception("Không thể làm thủ tục check-in ở trạng thái chuyến bay hiện tại: " . $this->getStatusString());
    }
}
