<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'flight_id',
        'ticket_code',
        'passenger_name',
        'identity_number',
        'seat_id',
        'ticket_price',
    ];

    // -------------------------------------------------------------------------
    // RELATIONSHIPS
    // -------------------------------------------------------------------------

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function seat()
    {
        return $this->belongsTo(Seat::class);
    }

    /**
     * A Ticket has one BoardingPass (QR code — issued at payment).
     * Ticket (1) → BoardingPass (1)
     */
    public function boardingPass()
    {
        return $this->hasOne(BoardingPass::class);
    }

    /**
     * A Ticket has one CheckIn record (created at physical airport check-in).
     * Ticket (1) → CheckIn (1)
     */
    public function checkIn()
    {
        return $this->hasOne(CheckIn::class);
    }
}