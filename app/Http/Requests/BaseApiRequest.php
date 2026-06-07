<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * BaseApiRequest
 *
 * Abstract base class for all API FormRequests in the SkyLink system.
 *
 * PATTERN: Template Method
 * ---
 * This class defines the skeleton algorithm for handling validation failures.
 * The hook method `failedValidation()` is overridden here ONCE to enforce a
 * unified JSON error response format across the entire API surface.
 * All concrete FormRequest classes inherit this behavior automatically,
 * satisfying DRY and SRP simultaneously.
 *
 * SOLID Compliance:
 * - SRP : This class has one single responsibility — define the contract for
 *         how validation failures are reported to the API consumer.
 * - OCP : Adding a new FormRequest never requires modifying this class.
 * - LSP : Every child FormRequest is a valid substitute for FormRequest.
 */
abstract class BaseApiRequest extends FormRequest
{
    /**
     * Intercepts the default Laravel validation failure behavior.
     *
     * By default, Laravel would redirect or return an unformatted response.
     * This override throws an HttpResponseException with a structured JSON
     * body that is consistent with the rest of the SkyLink API contract.
     *
     * @param Validator $validator The failed Validator instance.
     * @throws HttpResponseException Always throws to short-circuit the request lifecycle.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'status'  => 'error',
                'message' => 'Dữ liệu đầu vào không hợp lệ.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
