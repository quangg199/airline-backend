<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * BookingFactory
 *
 * Generates realistic Booking records for seeding and testing.
 * Provides named states for common lifecycle scenarios.
 *
 * Usage:
 *   Booking::factory()->create();                     // Random status
 *   Booking::factory()->paid()->create();             // Paid booking
 *   Booking::factory()->pending()->for($user)->create();
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            // Creates a new User/Flight if no existing one is provided via ->for()
            'user_id'      => User::factory(),
            'flight_id'    => Flight::factory(),
            'pnr_code'     => strtoupper(Str::random(6)),
            'total_amount' => fake()->numberBetween(5, 50) * 100000,
            'status'       => fake()->randomElement(['pending', 'paid', 'cancelled']),
        ];
    }

    // -------------------------------------------------------------------------
    // NAMED STATES — for precise test scenario setup
    // -------------------------------------------------------------------------

    /**
     * Set booking status to 'paid'.
     * Use when testing BookingObserver, QR generation, or seat assignment.
     */
    public function paid(): static
    {
        return $this->state(['status' => 'paid']);
    }

    /**
     * Set booking status to 'pending'.
     * Use when testing payment flow or cancellation.
     */
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    /**
     * Set booking status to 'cancelled'.
     * Use when testing cancellation refund flows.
     */
    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
