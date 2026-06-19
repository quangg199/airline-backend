<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\Seat;
use App\Models\Ticket;

class RescheduleService
{
    /**
     * Thực hiện cấp phát ghế và đổi chuyến bay
     */
    public function executeReschedule(Booking $booking, Flight $newFlight, array $newSeatIds = [], float $additionalAmount = 0): void
    {
        // Giải phóng ghế cũ
        foreach ($booking->tickets as $ticket) {
            $ticket->seat_id = null;
            $ticket->save();
        }

        // Gán ghế mới hoặc tự động tìm ghế có sẵn
        foreach ($booking->tickets as $index => $ticket) {
            $newSeatId = $newSeatIds[$index] ?? null;

            // Nếu không cung cấp seat_id, tự động tìm ghế trống
            if (!$newSeatId) {
                $oldSeat = $ticket->seat;
                $preferredClass = $oldSeat ? $oldSeat->seat_class : 'economy';
                
                // Tìm ghế trống cùng class hoặc ghế khác nếu không đủ
                $availableSeat = Seat::where('aircraft_id', $newFlight->aircraft_id)
                    ->where('seat_class', $preferredClass)
                    ->whereNotIn('id', Ticket::where('flight_id', $newFlight->id)
                        ->whereHas('booking', function ($q) {
                            $q->whereIn('status', ['pending', 'paid']);
                        })->pluck('seat_id'))
                    ->first();

                if (!$availableSeat) {
                    // Tìm ghế trống bất kỳ
                    $availableSeat = Seat::where('aircraft_id', $newFlight->aircraft_id)
                        ->whereNotIn('id', Ticket::where('flight_id', $newFlight->id)
                            ->whereHas('booking', function ($q) {
                                $q->whereIn('status', ['pending', 'paid']);
                            })->pluck('seat_id'))
                        ->first();
                }

                if (!$availableSeat) {
                    abort(422, 'Không có ghế trống trên chuyến bay mới.');
                }

                $newSeatId = $availableSeat->id;
            }

            $ticket->seat_id = $newSeatId;
            $ticket->flight_id = $newFlight->id;
            $ticket->save();
        }

        // Cập nhật booking với chuyến bay mới
        $booking->flight_id = $newFlight->id;
        $booking->total_amount += $additionalAmount;
        $booking->save();
    }
}
