<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
   public function show(Request $request)
{
    $user = $request->user()->load('roles');

    return response()->json([
        'status' => 'success',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'membership_tier' => $user->membership_tier,
            'roles' => $user->roles,
        ]
    ]);
}
}