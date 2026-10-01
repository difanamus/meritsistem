<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
