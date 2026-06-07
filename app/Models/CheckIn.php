<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * CheckIn Model
 *
 * Represents the physical airport check-in record for a passenger.
 * Created when the passenger checks in at the airport counter or kiosk.
 * Relationship: Ticket (1) → CheckIn (1)  [One-to-One]
 *
 * Domain Boundary (clarified in migration 2026_06_08_000002):
 * ---
 *   boarding_passes  = Issued automatically at payment confirmation.
 *                      Owns the QR code (qr_code_url).
 *                      "Your ticket exists and is valid."
 *
 *   check_ins        = Created at physical airport check-in.
 *                      Records boarding gate, boarding time, and
 *                      whether the passenger actually boarded.
 *                      "You have physically gone through the boarding gate."
 *
 * This model does NOT have qr_code_url — access QR via:
 *   $checkIn->ticket->boardingPass->qr_code_url
 *
 * SOLID Compliance:
 * - SRP : Anemic model — schema + relationships only.
 *         Check-in processing logic belongs in a dedicated CheckInService.
 */
class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'boarding_gate',
        'boarding_time',
        'is_boarded',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'boarding_time' => 'datetime',
            'is_boarded'    => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // RELATIONSHIPS
    // -------------------------------------------------------------------------

    /**
     * A CheckIn belongs to one Ticket.
     * Ticket (1) → CheckIn (1) — FK ticket_id is on this table.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
