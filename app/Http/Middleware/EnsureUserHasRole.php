<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Only let users with one of the given roles through; signs out suspended accounts.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => 'Your account has been suspended. Please contact support.']);
        }

        if (! in_array($user->role, $roles, true)) {
            return redirect()->route($user->dashboardRoute())->with('error', 'That page is not available for your account.');
        }

        return $next($request);
    }
}
