<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class ApiAuthenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        //  KHÔNG redirect về login nữa
        if (! $request->expectsJson()) {
            return null;
        }

        return null;
    }
}