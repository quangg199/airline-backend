<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add business-critical columns to the `payments` table.
 *
 * The original migration created an empty shell (id + timestamps only).
 * This migration adds all columns required to track a payment transaction
 * within the SkyLink booking lifecycle.
 *
 * Payment lifecycle:
 *   pending → success (booking status auto-updates via BookingObserver)
 *   pending → failed  (payment gateway callback returns failure)
 *
 * Design Notes:
 * - booking_id FK: 1 Booking has 1 Payment (1-1 relationship).
 *   cascade delete: if a booking is removed, its payment record is too.
 * - transaction_id: The external reference from the payment gateway
 *   (VNPay, MoMo, etc.). Nullable initially, filled after gateway confirms.
 * - payment_method: Stored as a string to remain open for extension —
 *   new gateways can be added without an ENUM column migration.
 * - paid_at: Null until the gateway confirms success. Enables auditing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Foreign key linking this payment to its booking (1-1)
            $table->foreignId('booking_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // External transaction ID from the payment gateway (VNPay, MoMo, Stripe...)
            // Nullable: assigned only after the gateway responds
            $table->string('transaction_id')->nullable()->unique()->after('booking_id');

            // Amount stored in VND (integer — no floating point for money)
            $table->unsignedInteger('amount')->after('transaction_id');

            // Payment gateway used: 'vnpay' | 'momo' | 'stripe' | 'cod' | ...
            // String (not ENUM) — OCP: new gateways never require a schema change
            $table->string('payment_method', 50)->after('amount');

            // Status of this payment attempt
            $table->enum('status', ['pending', 'success', 'failed'])
                ->default('pending')
                ->after('payment_method');

            // Timestamp when the payment was confirmed successful (null until then)
            $table->timestamp('paid_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn([
                'booking_id',
                'transaction_id',
                'amount',
                'payment_method',
                'status',
                'paid_at',
            ]);
        });
    }
};
