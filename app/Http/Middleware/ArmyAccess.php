<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ArmyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is logged in and has permission for locations
        if (auth()->check() && auth()->user()->canManageLocations()) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access Denied: You do not have permission to access this section.'
        ], 403);
    }
}