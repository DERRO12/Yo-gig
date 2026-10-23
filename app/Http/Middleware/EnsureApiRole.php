<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiRole
{
    /**
     * JSON counterpart of the "role" middleware for the mobile API: suspended accounts lose their token.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->is_active) {
            $request->attributes->get('api_token')?->delete();

            return response()->json(['message' => 'Your account has been suspended. Please contact support.'], 403);
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'This action is not available for your account.'], 403);
        }

        return $next($request);
    }
}
