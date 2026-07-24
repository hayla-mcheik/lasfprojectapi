<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PilotViewAccess
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user && $user->canViewPilots()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access Denied: Pilot viewing permission required.',
        ], 403);
    }
}