<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseApiRequest;

/**
 * RegisterRequest
 *
 * Handles input validation for the user registration endpoint.
 * POST /api/auth/register
 *
 * SOLID Compliance:
 * - SRP : This class is solely responsible for the validation rules of the
 *         "register a new user" use-case. Nothing more.
 * - ISP : Lean and focused — only exposes rules() and messages() relevant
 *         to registration. Does not bleed concerns from other auth flows.
 *
 * Business Rules Encoded:
 * - name    : Required, plain string, max 255 chars.
 * - email   : Required, must be a valid email format, globally unique in `users` table.
 * - password: Required, minimum 6 chars, must be confirmed by `password_confirmation`.
 */
class RegisterRequest extends BaseApiRequest
{
    /**
     * All API routes using auth:sanctum or public registration are
     * authorized at the route/middleware level. FormRequest authorization
     * is always TRUE here — the middleware layer is the Proxy Guard.
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
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    /**
     * Human-readable Vietnamese error messages for API consumers (FE team).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'Họ tên là bắt buộc.',
            'name.max'           => 'Họ tên không được vượt quá 255 ký tự.',
            'email.required'     => 'Email là bắt buộc.',
            'email.email'        => 'Định dạng email không hợp lệ.',
            'email.unique'       => 'Email này đã được sử dụng bởi tài khoản khác.',
            'password.required'  => 'Mật khẩu là bắt buộc.',
            'password.min'       => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ];
    }
}
