<?php

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

        // Register the CheckRole Protection Proxy as a named alias.
        // Routes can now declare: ->middleware('role:admin') or ->middleware('role:admin,staff')
        // This follows DIP: route definitions depend on the 'role' abstraction,
        // not on the App\Http\Middleware\CheckRole concrete class.
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        /**
         * HANDLER 1: ModelNotFoundException (HTTP 404)
         * ---
         * Triggered by: Model::findOrFail(), Model::firstOrFail()
         * Without this handler, Laravel returns an HTML 404 page that crashes
         * any JSON-consuming frontend.
         */
        $exceptions->render(function (ModelNotFoundException $e, Request $request): mixed {
            if ($request->expectsJson()) {
                // Extract the short model name for a developer-friendly message
                $modelName = class_basename($e->getModel());

                return response()->json([
                    'status'  => 'error',
                    'message' => "Không tìm thấy dữ liệu {$modelName} được yêu cầu.",
                ], 404);
            }

            return null; // Let Laravel handle non-JSON (web) requests normally
        });

        /**
         * HANDLER 2: NotFoundHttpException (HTTP 404)
         * ---
         * Triggered by: abort(404), or accessing a URL that matches no route at all.
         * Separate from ModelNotFoundException — this is a routing-level 404.
         */
        $exceptions->render(function (NotFoundHttpException $e, Request $request): mixed {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Đường dẫn API không tồn tại.',
                ], 404);
            }

            return null;
        });

        /**
         * HANDLER 3: AuthenticationException (HTTP 401)
         * ---
         * Triggered by: auth:sanctum middleware when token is missing, invalid, or expired.
         * Without this handler, Sanctum redirects to '/login' (HTTP 302) — completely
         * wrong behavior for a stateless REST API consumed by ReactJS.
         */
        $exceptions->render(function (AuthenticationException $e, Request $request): mixed {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Chưa xác thực. Vui lòng đăng nhập để tiếp tục.',
                ], 401);
            }

            return null;
        });

        /**
         * HANDLER 4: NoAvailableSeatsException (HTTP 422)
         * ---
         * Triggered by: SeatAssignmentService::assignSeat() inside BookingObserver.
         * This is a known, expected domain error — NOT a server crash.
         * HTTP 422 (Unprocessable Entity) is the correct semantic: the request was
         * valid, but the business state prevents fulfillment.
         */
        $exceptions->render(function (NoAvailableSeatsException $e, Request $request): mixed {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return null;
        });

        /**
         * HANDLER 5: Generic Fallback — Throwable (HTTP 500)
         * ---
         * Safety net for any unexpected exception not caught by the handlers above.
         * CRITICAL: Hides raw stacktrace from API consumers in production.
         * In development (APP_DEBUG=true), the message is exposed for debugging.
         * In production (APP_DEBUG=false), a safe generic message is returned.
         */
        $exceptions->render(function (\Throwable $e, Request $request): mixed {
            if ($request->expectsJson()) {
                $isDebug  = config('app.debug');
                $message  = $isDebug
                    ? '[DEBUG] ' . $e->getMessage()
                    : 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';

                return response()->json([
                    'status'  => 'error',
                    'message' => $message,
                ], 500);
            }

            return null; // Let Laravel's default HTML error page handle web requests
        });

    })->create();

