<?php

namespace App\Http\Requests\Booking;

use App\Http\Requests\BaseApiRequest;

/**
 * StoreBookingRequest
 *
 * Handles input validation for the create booking endpoint.
 * POST /api/bookings
 *
 * SOLID Compliance:
 * - SRP : Solely responsible for validating the "create a new booking" input contract.
 *         Price calculation, seat assignment, and DB writes are NOT its concern.
 * - ISP : Focused on the booking creation shape only. A future
 *         `UpdateBookingRequest` or `CancelBookingRequest` will be separate classes.
 * - DIP : The Controller depends on this abstraction (FormRequest contract), not on
 *         the concrete Validator facade — decoupled.
 *
 * Business Rules Encoded:
 * - flight_id      : Must exist as a valid record in the `flights` table.
 * - passenger_name : Standard string, required for the ticket.
 * - identity_number: CCCD/Passport number. Required, string, max 50.
 * - service_ids    : Optional array of service IDs. Each ID must exist in `services`.
 * - seat_class     : Optional. If provided, must be one of the allowed enum values.
 */
class StoreBookingRequest extends BaseApiRequest
{
    /**
     * Authorization is handled at the route level via `auth:sanctum` middleware.
     * This FormRequest trusts that the middleware Proxy Guard has already
     * verified the user's identity before this point.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string|array<string>>
     */
    public function rules(): array
    {
        return [
            'flight_id'        => ['required', 'integer', 'exists:flights,id'],
            'return_flight_id' => ['nullable', 'integer', 'exists:flights,id'],
            'passenger_name'   => ['required', 'string', 'max:255'],
            'identity_number'  => ['required', 'string', 'max:50'],
            'service_ids'      => ['nullable', 'array'],
            'service_ids.*'    => ['integer', 'exists:services,id'],
            'seat_class'       => ['nullable', 'string', 'in:economy,business'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'flight_id.required'       => 'Vui lòng chọn chuyến bay đi.',
            'flight_id.integer'        => 'Mã chuyến bay không hợp lệ.',
            'flight_id.exists'         => 'Chuyến bay đi không tồn tại.',
            'return_flight_id.integer' => 'Mã chuyến bay về không hợp lệ.',
            'return_flight_id.exists'  => 'Chuyến bay về không tồn tại.',
            'passenger_name.required'  => 'Tên hành khách là bắt buộc.',
            'passenger_name.max'       => 'Tên hành khách không được vượt quá 255 ký tự.',
            'identity_number.required' => 'Số CCCD/Hộ chiếu là bắt buộc.',
            'identity_number.max'      => 'Số CCCD/Hộ chiếu không được vượt quá 50 ký tự.',
            'service_ids.array'        => 'Danh sách dịch vụ phải là một mảng.',
            'service_ids.*.integer'    => 'Mã dịch vụ không hợp lệ.',
            'service_ids.*.exists'     => 'Một hoặc nhiều dịch vụ được chọn không tồn tại.',
            'seat_class.in'            => 'Hạng ghế phải là "economy" hoặc "business".',
        ];
    }
}
