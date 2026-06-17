<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\User;
use App\Models\Payment;
use App\Models\Ticket;

class CreateTestBooking extends Command
{
    protected $signature = 'booking:create-test';
    protected $description = 'Create a test booking (PNR: ABC123) for check-in testing';

    public function handle()
    {
        try {
            $flight = Flight::first();
            if (!$flight) {
                $this->error('❌ No flights found. Run php artisan migrate --seed first.');
                return 1;
            }

            // Ensure flight is in 'check_in' state so check-in is allowed
            $flight->update(['status' => 'check_in']);

            $user = User::first() ?? User::factory()->create();

            $pnr = 'ABC123';

            $booking = Booking::firstOrCreate(
                ['pnr_code' => $pnr],
                [
                    'user_id' => $user->id,
                    'flight_id' => $flight->id,
                    'total_amount' => 500000,
                    'status' => 'paid',
                    'expires_at' => now()->addDays(1),
                ]
            );

            Payment::firstOrCreate(
                ['booking_id' => $booking->id],
                ['amount' => $booking->total_amount, 'status' => 'success', 'payment_method' => 'test', 'paid_at' => now()]
            );

            Ticket::firstOrCreate(
                ['booking_id' => $booking->id, 'passenger_name' => 'Nguyen Van A'],
                [
                    'flight_id' => $flight->id,
                    'ticket_code' => Str::upper(Str::random(8)),
                    'identity_number' => null,
                    'seat_id' => null,
                    'ticket_price' => $booking->total_amount,
                ]
            );

            // Delete old boarding pass if exists to avoid constraint errors
            $ticket = Ticket::where('booking_id', $booking->id)
                ->where('passenger_name', 'Nguyen Van A')
                ->first();
            if ($ticket) {
                $ticket->boardingPass()->delete();
            }

            $this->info('✅ Test booking created successfully!');
            $this->info("PNR: {$pnr}");
            $this->info('Passenger: Nguyen Van A');
            $this->info('Flight: ' . $flight->flight_number);
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
