<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

/**
 * LoginRequest
 *
 * Handles input validation for the user login endpoint.
 * POST /api/auth/login
 *
 * SOLID Compliance:
 * - SRP : Solely responsible for validating "login" input shape.
 *         Credential verification (Auth::attempt) remains in the Service/Controller.
 * - ISP : Minimal surface — only email + password. No bleed from Register rules.
 *
 * Business Rules Encoded:
 * - email   : Required, valid email format. (Existence check is Auth::attempt's job,
 *             NOT the validator's — avoid leaking "email not found" info to attackers.)
 * - password: Required, string only.
 */
class LoginRequest extends BaseApiRequest
{
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
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'Email là bắt buộc.',
            'email.email'       => 'Định dạng email không hợp lệ.',
            'password.required' => 'Mật khẩu là bắt buộc.',
        ];
    }
}
