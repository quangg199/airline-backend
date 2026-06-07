<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 *
 * PATTERN: Proxy Pattern (Protection Proxy)
 * ---
 * This middleware acts as a transparent Protection Proxy sitting between
 * the Route and the Controller. It intercepts every incoming request and
 * verifies the authenticated user possesses at least one of the required
 * roles before allowing the request to proceed to its intended target.
 *
 * The Controller (the "Real Subject") remains completely unaware of this
 * proxy guard — satisfying the Proxy Pattern's transparency requirement.
 *
 * SOLID Compliance:
 * - SRP : This class has one responsibility — verify role-based authorization.
 *         It does NOT handle authentication (that is auth:sanctum's job).
 * - OCP : Supporting a new role (e.g., 'staff') requires zero modification
 *         to this class — just pass 'role:staff' in the route definition.
 * - LSP : Implements the standard Laravel middleware contract, fully
 *         substitutable within Laravel's middleware pipeline.
 * - DIP : Route definitions depend on the 'role' alias (abstraction),
 *         not on this concrete class directly.
 *
 * Usage in routes:
 *   Route::middleware(['auth:sanctum', 'role:admin'])->group(...);
 *   Route::middleware(['auth:sanctum', 'role:admin,staff'])->group(...);
 *
 * Security Note:
 *   This middleware MUST always be used AFTER 'auth:sanctum' in the chain.
 *   If placed before, $request->user() will be null and the guard will fail.
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Checks whether the authenticated user has at least one of the
     * specified roles. Roles are passed as variadic arguments from the
     * route definition (e.g., 'role:admin' or 'role:admin,staff').
     *
     * @param  Request  $request   The incoming HTTP request.
     * @param  Closure  $next      The next middleware/controller in the pipeline.
     * @param  string[] ...$roles  One or more required role names.
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Defensive check: ensure user is authenticated before role check.
        // In practice, auth:sanctum will have already rejected unauthenticated
        // requests — this is a safety net for misconfigured route chains.
        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Chưa xác thực. Vui lòng đăng nhập để tiếp tục.',
            ], 401);
        }

        // Load the user's roles as a flat array of role name strings.
        // Uses Eloquent's pluck() for a clean, efficient single-column extraction.
        $userRoles = $user->roles()->pluck('name')->toArray();

        // Check if the user holds ANY of the required roles (OR logic).
        // This design intentionally uses OR — a user with role 'admin' OR 'staff'
        // can access an endpoint requiring 'role:admin,staff'.
        foreach ($roles as $role) {
            if (in_array($role, $userRoles, strict: true)) {
                return $next($request);
            }
        }

        // User is authenticated but lacks the required role — HTTP 403 Forbidden.
        // 403 is semantically distinct from 401: the identity is known,
        // but the permission is denied.
        return response()->json([
            'status'  => 'error',
            'message' => 'Bạn không có quyền truy cập tài nguyên này.',
        ], 403);
    }
}
