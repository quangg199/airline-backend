<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    /**
     * Cập nhật thông tin profile (email, password)
     * Yêu cầu nhập đúng mật khẩu hiện tại (current_password).
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'new_password' => ['nullable', 'string', 'min:6'],
        ]);

        // Xác nhận mật khẩu hiện tại
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mật khẩu hiện tại không chính xác.',
            ], 400);
        }

        $changes = [];

        // Cập nhật email nếu có và khác với email hiện tại
        if (!empty($validated['email']) && $validated['email'] !== $user->email) {
            $user->email = $validated['email'];
            // Tùy chọn: reset email_verified_at nếu cần xác minh lại email
            // $user->email_verified_at = null; 
            $changes[] = 'email';
        }

        // Cập nhật mật khẩu nếu có
        if (!empty($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
            $changes[] = 'mật khẩu';
        }

        if (empty($changes)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Không có thay đổi nào được thực hiện.',
                'data' => $user
            ]);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật ' . implode(' và ', $changes) . ' thành công!',
            'data' => $user
        ]);
    }
}
