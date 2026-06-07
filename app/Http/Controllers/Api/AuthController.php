<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản mới
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Validation is fully handled by RegisterRequest (Chain of Responsibility).
        // $request->validated() returns only the fields that passed the rules —
        // safe from mass-assignment attacks by design.
        $validated = $request->validated();

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'membership_tier' => 'standard',
        ]);

        // Assign the default 'member' role (Proxy Guard will use this for CheckRole middleware)
        $memberRole = Role::where('name', 'member')->first();
        if ($memberRole) {
            $user->roles()->attach($memberRole);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => 'Đăng ký tài khoản thành công.',
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }

    /**
     * Đăng nhập và lấy Token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // LoginRequest has already validated the shape (email format, presence).
        // Auth::attempt handles credential verification — these are separate concerns.
        $validated = $request->validated();

        if (!Auth::attempt($validated)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email hoặc mật khẩu không chính xác.',
            ], 401);
        }

        $user  = User::where('email', $validated['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user,
        ]);
    }

    /**
     * Lấy thông tin user đang đăng nhập
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $request->user()->load('roles'),
        ]);
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke only the current token (not all tokens — supports multi-device login)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Đăng xuất thành công.',
        ]);
    }
}
