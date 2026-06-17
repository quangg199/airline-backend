<?php

namespace App\Repositories;

use App\Models\Booking;

class BookingRepository implements BookingRepositoryInterface
{
    public function getAll()
    {
        return Booking::with([
            'user',
            'flight',
            'payment'
        ])->latest()->get();
    }

    public function findById(int $id)
    {
        return Booking::with([
            'user',
            'flight',
            'tickets',
            'services',
            'payment'
        ])->findOrFail($id);
    }

    public function delete(int $id)
    {
        $booking = Booking::findOrFail($id);

        return $booking->delete();
    }
}