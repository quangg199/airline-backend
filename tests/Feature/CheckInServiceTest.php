<?php

namespace Tests\Feature;

use App\Contracts\CheckInServiceInterface;
use App\Facades\CheckInFacade;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\Seat;
use App\Models\User;
use App\Services\CheckInService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInServiceTest extends TestCase
{
    use RefreshDatabase;

    private Airport $departureAirport;
    private Airport $arrivalAirport;
    private Aircraft $aircraft;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup basic user, airports and aircraft for tests
        $this->user = User::create([
            'name' => 'John Passenger',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->departureAirport = Airport::create([
            'code' => 'HAN',
            'name' => 'Noi Bai International Airport',
            'city' => 'Hanoi',
        ]);

        $this->arrivalAirport = Airport::create([
            'code' => 'SGN',
            'name' => 'Tan Son Nhat International Airport',
            'city' => 'Ho Chi Minh City',
        ]);

        $this->aircraft = Aircraft::create([
            'model' => 'Airbus A320',
            'tail_number' => 'VN-A320',
            'status' => 'active',
        ]);
    }

    /**
     * Test that CheckInServiceInterface is correctly bound to CheckInService in the IoC container.
     */
    public function test_check_in_service_interface_is_bound(): void
    {
        $service = app(CheckInServiceInterface::class);
        $this->assertInstanceOf(CheckInService::class, $service);
    }

    /**
     * Test that CheckInFacade correctly resolves the interface.
     */
    public function test_check_in_facade_resolves_correctly(): void
    {
        // Calling static methods on facade works
        $resolved = CheckInFacade::getFacadeRoot();
        $this->assertInstanceOf(CheckInService::class, $resolved);
    }

    /**
     * Helper to create a valid booking, ticket, and payment for testing.
     */
    private function createBookingAndTicket(Flight $flight, string $passengerName = 'John Doe'): array
    {
        $booking = Booking::create([
            'user_id' => $this->user->id,
            'flight_id' => $flight->id,
            'pnr_code' => 'ABCDEF',
            'total_amount' => 1000000,
            'status' => 'paid',
        ]);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 1000000,
            'payment_method' => 'vnpay',
            'status' => 'success',
            'transaction_id' => 'TX123456',
        ]);

        $seat = Seat::create([
            'aircraft_id' => $this->aircraft->id,
            'seat_number' => '12A',
            'seat_class' => 'economy',
            'status' => 'available',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'flight_id' => $flight->id,
            'seat_id' => $seat->id,
            'passenger_name' => $passengerName,
            'identity_number' => 'ID12345',
            'ticket_code' => 'T1234567',
            'ticket_price' => 1000000,
        ]);

        return [$booking, $ticket];
    }

    /**
     * Test check-in within valid check-in window for a Scheduled flight.
     * It should transition the flight state to 'check_in' and check-in successfully.
     */
    public function test_scheduled_flight_check_in_success_and_transitions_state(): void
    {
        $flight = Flight::create([
            'flight_number' => 'VN123',
            'departure_airport_id' => $this->departureAirport->id,
            'arrival_airport_id' => $this->arrivalAirport->id,
            'aircraft_id' => $this->aircraft->id,
            'departure_time' => Carbon::now()->addHours(10), // Within 2h-24h window
            'arrival_time' => Carbon::now()->addHours(12),
            'base_price' => 1000000,
            'available_seats' => 180,
            'status' => 'scheduled',
        ]);

        $passengerName = 'John Doe';
        $this->createBookingAndTicket($flight, $passengerName);

        // Run check-in
        $service = app(CheckInServiceInterface::class);
        $result = $service->checkIn('ABCDEF', $passengerName);

        $this->assertNotEmpty($result);
        $this->assertEquals('VN123', $result['flight_number']);
        $this->assertEquals('12A', $result['seat_number']);

        // Check if flight state transitioned to 'check_in'
        $flight->refresh();
        $this->assertEquals('check_in', $flight->status);
    }

    /**
     * Test check-in when check-in is not open yet (e.g. 30 hours before flight).
     */
    public function test_scheduled_flight_check_in_not_open_yet_throws_exception(): void
    {
        $flight = Flight::create([
            'flight_number' => 'VN123',
            'departure_airport_id' => $this->departureAirport->id,
            'arrival_airport_id' => $this->arrivalAirport->id,
            'aircraft_id' => $this->aircraft->id,
            'departure_time' => Carbon::now()->addHours(30), // > 24h
            'arrival_time' => Carbon::now()->addHours(32),
            'base_price' => 1000000,
            'available_seats' => 180,
            'status' => 'scheduled',
        ]);

        $passengerName = 'John Doe';
        $this->createBookingAndTicket($flight, $passengerName);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check-in chưa mở');

        $service = app(CheckInServiceInterface::class);
        $service->checkIn('ABCDEF', $passengerName);
    }

    /**
     * Test check-in when check-in is already closed (e.g. 1 hour before flight).
     */
    public function test_scheduled_flight_check_in_already_closed_throws_exception(): void
    {
        $flight = Flight::create([
            'flight_number' => 'VN123',
            'departure_airport_id' => $this->departureAirport->id,
            'arrival_airport_id' => $this->arrivalAirport->id,
            'aircraft_id' => $this->aircraft->id,
            'departure_time' => Carbon::now()->addHours(1), // < 2h
            'arrival_time' => Carbon::now()->addHours(3),
            'base_price' => 1000000,
            'available_seats' => 180,
            'status' => 'scheduled',
        ]);

        $passengerName = 'John Doe';
        $this->createBookingAndTicket($flight, $passengerName);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check-in đã đóng');

        $service = app(CheckInServiceInterface::class);
        $service->checkIn('ABCDEF', $passengerName);
    }

    /**
     * Test check-in when flight is in check_in state and time is valid.
     */
    public function test_check_in_state_flight_check_in_success(): void
    {
        $flight = Flight::create([
            'flight_number' => 'VN123',
            'departure_airport_id' => $this->departureAirport->id,
            'arrival_airport_id' => $this->arrivalAirport->id,
            'aircraft_id' => $this->aircraft->id,
            'departure_time' => Carbon::now()->addHours(10), // Within valid time
            'arrival_time' => Carbon::now()->addHours(12),
            'base_price' => 1000000,
            'available_seats' => 180,
            'status' => 'check_in',
        ]);

        $passengerName = 'John Doe';
        $this->createBookingAndTicket($flight, $passengerName);

        $service = app(CheckInServiceInterface::class);
        $result = $service->checkIn('ABCDEF', $passengerName);

        $this->assertNotEmpty($result);
        $this->assertEquals('check_in', $flight->fresh()->status);
    }

    /**
     * Test check-in when flight is in boarding state.
     */
    public function test_boarding_flight_throws_exception(): void
    {
        $flight = Flight::create([
            'flight_number' => 'VN123',
            'departure_airport_id' => $this->departureAirport->id,
            'arrival_airport_id' => $this->arrivalAirport->id,
            'aircraft_id' => $this->aircraft->id,
            'departure_time' => Carbon::now()->addHours(10),
            'arrival_time' => Carbon::now()->addHours(12),
            'base_price' => 1000000,
            'available_seats' => 180,
            'status' => 'boarding',
        ]);

        $passengerName = 'John Doe';
        $this->createBookingAndTicket($flight, $passengerName);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Không thể làm thủ tục check-in ở trạng thái chuyến bay hiện tại: boarding');

        $service = app(CheckInServiceInterface::class);
        $service->checkIn('ABCDEF', $passengerName);
    }
}
