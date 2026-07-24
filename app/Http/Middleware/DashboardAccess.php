<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DashboardAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (
            auth()->check()
            && auth()->user()->canAccessDashboard()
        ) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access denied.'
        ],403);
    }
}