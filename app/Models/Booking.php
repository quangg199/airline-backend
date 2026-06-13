<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'flight_id',
        'pnr_code',
        'total_amount',
        'status',
        'expires_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class)
                    ->withPivot('quantity', 'price_at_purchase')
                    ->withTimestamps();
    }

    /**
     * A Booking has one Payment record.
     * Booking (1) → Payment (1)
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}