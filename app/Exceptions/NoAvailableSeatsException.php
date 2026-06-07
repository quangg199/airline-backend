<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * NoAvailableSeatsException
 *
 * Domain-specific exception representing the business rule violation
 * where a flight has no remaining seats for assignment.
 *
 * PATTERN: Custom Exception (Domain Exception)
 * ---
 * This is NOT a generic \Exception. It carries explicit semantic meaning
 * within the SkyLink bounded context — "a seat could not be assigned
 * because the flight's inventory is exhausted."
 *
 * SOLID Compliance:
 * - SRP : This class has one responsibility — identify the "no seats" failure state.
 * - OCP : The global exception handler in bootstrap/app.php can render this exception
 *         differently without ever modifying SeatAssignmentService or BookingObserver.
 * - LSP : Extends RuntimeException — safely substitutable wherever RuntimeException
 *         is caught, while still being catchable by its own specific type.
 *
 * Usage:
 *   throw new NoAvailableSeatsException('Chuyến bay VN123 đã hết ghế trống.');
 */
class NoAvailableSeatsException extends RuntimeException
{
    /**
     * @param string $message  Human-readable description of the seat shortage.
     * @param int    $code     Optional HTTP-hint code (defaults to 0, mapped to 422 in handler).
     */
    public function __construct(
        string $message = 'Chuyến bay này hiện không còn ghế trống.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
