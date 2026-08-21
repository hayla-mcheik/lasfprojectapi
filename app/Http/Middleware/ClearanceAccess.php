<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearanceAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (
            auth()->check() &&
            auth()->user()->canManageClearance()
        ) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied: you do not have permission to manage clearance statuses.'
        ], 403);
    }
}