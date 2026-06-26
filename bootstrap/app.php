<?php

// GitHub Copilot: Please describe the problem you are seeing (error message, stack trace, or unexpected behavior).

use App\Exceptions\NoAvailableSeatsException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleCors::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // ==============================
        // 404 Model Not Found
        // ==============================
        $exceptions->render(function (ModelNotFoundException $e, Request $request): mixed {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Không tìm thấy dữ liệu.',
                ], 404);
            }

            return null;
        });

        // ==============================
        // 404 Route Not Found
        // ==============================
        $exceptions->render(function (NotFoundHttpException $e, Request $request): mixed {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'API không tồn tại.',
                ], 404);
            }

            return null;
        });

        // ==============================
        // 401 AUTH FIX (QUAN TRỌNG)
        // ==============================
        // QUAN TRỌNG: chặn redirect /login gây crash
        $exceptions->render(function (AuthenticationException $e, Request $request): mixed {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        });

        // ==============================
        // Business Exception
        // ==============================
        $exceptions->render(function (NoAvailableSeatsException $e, Request $request): mixed {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return null;
        });

        // ==============================
        // 500 ERROR FALLBACK
        // ==============================
        $exceptions->render(function (\Throwable $e, Request $request): mixed {
            if ($request->is('api/*') || $request->expectsJson()) {

                $isDebug = config('app.debug');

                return response()->json([
                    'status'  => 'error',
                    'message' => $isDebug
                        ? $e->getMessage()
                        : 'Server error. Please try again later.',
                ], 500);
            }

            return null;
        });

    })
    ->create();