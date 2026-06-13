<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasFactory;

    protected $fillable = [
        'flight_number',
        'departure_airport_id',
        'arrival_airport_id',
        'departure_time',
        'arrival_time',
         'aircraft_id',
        'base_price',
        'available_seats',
        'status'    
    ];

    public function departureAirport()
    {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }

    public function arrivalAirport()
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function aircraft()
    {
        return $this->belongsTo(Aircraft::class);
    }

    /**
     * Lấy object State hiện tại dựa trên cột status.
     * Factory Pattern được sử dụng ở đây để khởi tạo State tương ứng.
     */
    public function state(): \App\States\Flight\FlightState
    {
        return match ($this->status) {
            'scheduled' => new \App\States\Flight\ScheduledState($this),
            'check_in'  => new \App\States\Flight\CheckInState($this),
            'boarding'  => new \App\States\Flight\BoardingState($this),
            'in_flight' => new \App\States\Flight\InFlightState($this),
            'arrived'   => new \App\States\Flight\ArrivedState($this),
            'delayed'   => new \App\States\Flight\DelayedState($this),
            'cancelled' => new \App\States\Flight\CancelledState($this),
            default     => new \App\States\Flight\ScheduledState($this), // Fallback an toàn
        };
    }

    /**
     * Delegate việc chuyển đổi trạng thái cho State object hiện tại xử lý.
     * Nếu hợp lệ, State object sẽ tự động lưu vào DB.
     * 
     * @param string $newStatusString
     * @throws \Exception
     */
    public function transitionTo(string $newStatusString): void
    {
        $this->state()->transitionTo($newStatusString);
    }
}