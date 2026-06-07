<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * BookingPolicy
 *
 * Implements resource-level authorization (Protection Proxy).
 * Ensures that users can only access bookings they own, unless they
 * possess elevated privileges (admin).
 *
 * SOLID Compliance:
 * - SRP : This class only handles authorization rules for the Booking model.
 * - OCP : New rules (e.g., 'update' or 'delete') can be added without modifying
 *         existing methods.
 */
class BookingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the booking.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Booking  $booking
     * @return bool
     */
    public function view(User $user, Booking $booking): bool
    {
        // 1. Nếu là Admin -> Cho phép xem tất cả
        if ($user->roles()->where('name', 'admin')->exists()) {
            return true;
        }

        // 2. Nếu là Member -> Chỉ được xem booking của chính mình
        return $user->id === $booking->user_id;
    }

    /**
     * Determine whether the user can create bookings.
     */
    public function create(User $user): bool
    {
        return true; // Bất kỳ user đã đăng nhập nào cũng được tạo booking
    }
}
