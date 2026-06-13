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
}
