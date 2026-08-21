<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LocationViewAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (
            auth()->check() &&
            auth()->user()->canViewLocations()
        ) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied: you do not have permission to view locations.'
        ], 403);
    }
}