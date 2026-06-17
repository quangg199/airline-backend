<?php

namespace App\Services;

use App\Repositories\BookingRepositoryInterface;

class BookingService
{
    protected BookingRepositoryInterface $bookingRepository;

    public function __construct(
        BookingRepositoryInterface $bookingRepository
    ) {
        $this->bookingRepository = $bookingRepository;
    }

    public function getAllBookings()
    {
        return $this->bookingRepository->getAll();
    }

    public function getBookingById(int $id)
    {
        return $this->bookingRepository->findById($id);
    }

    public function deleteBooking(int $id)
    {
        return $this->bookingRepository->delete($id);
    }
}