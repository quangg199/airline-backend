<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Ticket;
use App\Models\BoardingPass;
use App\Services\SeatAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingObserver
{
    public function __construct(
        protected SeatAssignmentService $seatAssignmentService
    ) {}

    public function updated(Booking $booking): void
    {
        // chỉ khi thanh toán thành công
        if (
                !$booking->wasChanged('status') ||
                $booking->status !== 'paid'
            ) {
                return;
            }

        // tránh duplicate
        if ($booking->tickets()->exists()) {
            return;
        }

        $booking->load(['user', 'flight']);

        if (!$booking->flight || !$booking->flight->aircraft_id) {
            return;
        }

        DB::transaction(function () use ($booking) {

            // 1. assign seat
            $seat = $this->seatAssignmentService
                ->assignSeat($booking->flight->aircraft_id);

            // 2. create ticket
            $ticket = Ticket::create([
                'booking_id' => $booking->id,
                'flight_id' => $booking->flight_id,

                'ticket_code' => 'TKT-' . strtoupper(Str::random(8)),

                'passenger_name' => $booking->user->name,

                'identity_number' => 'TEMP-' . rand(100000, 999999),

                'seat_id' => $seat->id,

                'ticket_price' => $booking->total_amount,

                'status' => 'active'
            ]);
            // 3. QR DATA
            $qrData = json_encode([
                'ticket_id' => $ticket->id,
                'pnr_code' => $booking->pnr_code,
                'seat' => $seat->seat_number
            ]);

            // SVG instead of PNG
            $fileName = 'qrcodes/' . Str::uuid() . '.svg';

            Storage::disk('public')->put(
                $fileName,
                QrCode::format('svg')
                    ->size(300)
                    ->generate($qrData)
            );

            // 4. boarding pass
            BoardingPass::create([
                'ticket_id' => $ticket->id,
                'qr_code_url' => $fileName,
                'gate' => 'A1'
            ]);

            // 5. Cập nhật hạng thành viên VIP (gold) nếu đủ 5 vé thương gia
            if ($booking->user) {
                $user = $booking->user;
                if ($user->business_class_ticket_count >= 5 && $user->membership_tier !== 'gold') {
                    $user->update(['membership_tier' => 'gold']);
                }
            }
        });
    }
}