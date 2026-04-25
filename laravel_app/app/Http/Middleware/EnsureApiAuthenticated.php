<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check for either 'auth_user' (new) or 'user' (old PHP compat)
        if (!$request->session()->has('auth_user') && !$request->session()->has('user')) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Please log in',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Web requests should redirect to the login page.
            return redirect('/login');
        }

        return $next($request);
    }
}
