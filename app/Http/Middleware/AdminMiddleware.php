<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
// app/Http/Middleware/AdminMiddleware.php

public function handle(Request $request, Closure $next)
{
    // ONLY allow Super Admins (is_admin = 1)
    if (auth()->check() && auth()->user()->is_admin == 1) {
        return $next($request);
    }

    // Block everyone else, including the Army role
    return response()->json([
        'success' => false,
        'message' => 'Access Denied: Super Admin privileges required.'
    ], 403);
}
}

