<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clarify the role boundary between `check_ins` and `boarding_passes`.
 *
 * PROBLEM (identified in architecture audit):
 * Both `check_ins` and `boarding_passes` stored a `qr_code_url` column,
 * creating ambiguity about which table owns the QR code.
 *
 * RESOLUTION — Clear domain boundary:
 *
 *   boarding_passes  = Created when payment is confirmed (BookingObserver).
 *                      Holds the QR code image path. This IS the boarding pass.
 *                      Relationship: Ticket (1-1) → BoardingPass
 *
 *   check_ins        = Created when passenger physically checks in at the airport.
 *                      Records gate, boarding time, and whether they boarded.
 *                      Does NOT need its own QR code — it references the
 *                      boarding_pass which already owns the QR.
 *                      Relationship: Ticket (1-1) → CheckIn
 *
 * This migration removes the redundant `qr_code_url` from `check_ins`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            // Remove the redundant qr_code_url — QR code is owned by boarding_passes
            $table->dropColumn('qr_code_url');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            // Restore qr_code_url for rollback compatibility
            $table->string('qr_code_url')->nullable()->after('boarding_time');
        });
    }
};
