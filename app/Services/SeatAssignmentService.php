<?php

namespace App\Services;

use App\Exceptions\NoAvailableSeatsException;
use App\Models\Seat;

/**
 * SeatAssignmentService
 *
 * Responsible for atomically finding and reserving an available seat
 * on a given aircraft. Must always be called within a DB::transaction()
 * to guarantee the lockForUpdate() pessimistic lock is honoured.
 *
 * SOLID Compliance:
 * - SRP : Handles one concern — seat reservation logic.
 * - DIP : Throws a domain abstraction (NoAvailableSeatsException), not a
 *         concrete infrastructure exception (\Exception), so callers
 *         depend on the domain contract, not the implementation detail.
 */
class SeatAssignmentService
{
    /**
     * Atomically assign the first available seat for the given aircraft.
     *
     * Uses a pessimistic lock (SELECT ... FOR UPDATE) to prevent race conditions
     * when multiple bookings are processed concurrently for the same flight.
     * This method MUST be called inside a DB::transaction().
     *
     * @param  int  $aircraftId
     * @return Seat The reserved seat record (status updated to 'booked').
     * @throws NoAvailableSeatsException When the flight has no remaining seats.
     */
    public function assignSeat(int $aircraftId): Seat
    {
        $seat = Seat::where('aircraft_id', $aircraftId)
            ->where('status', 'available')
            ->lockForUpdate() // Pessimistic lock — prevents double-booking under concurrency
            ->first();

        if (!$seat) {
            throw new NoAvailableSeatsException(
                'Chuyến bay này hiện không còn ghế trống.'
            );
        }

        $seat->update(['status' => 'booked']);

        return $seat;
    }
}