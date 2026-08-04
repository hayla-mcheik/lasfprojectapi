<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BeirutAirportAccess
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $user = $request->user();

        if (
            $user &&
            (
                $user->is_admin ||
                $user->role === 'beirut_airport'
            )
        ) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied.'
        ], 403);
    }
}