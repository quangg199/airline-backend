<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Payment Model
 *
 * Represents a single payment transaction attempt for a Booking.
 * Relationship: Booking (1) → Payment (1)  [One-to-One]
 *
 * SOLID Compliance:
 * - SRP : Anemic model — holds schema definition and relationships only.
 *         All payment processing logic lives in a dedicated PaymentService
 *         (to be built) or a Gateway Adapter (MomoAdapter, VnPayAdapter).
 *
 * Money Rule:
 *   `amount` is stored as an integer (VND has no sub-unit).
 *   NEVER use float/double for monetary values — floating point
 *   precision errors will cause accounting discrepancies.
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'transaction_id',
        'amount',
        'payment_method',
        'status',
        'paid_at',
    ];

    /**
     * Attribute casting for correct PHP types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount'  => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // RELATIONSHIPS
    // -------------------------------------------------------------------------

    /**
     * A Payment belongs to one Booking.
     * Booking (1) → Payment (1) — the foreign key booking_id lives on this table.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
