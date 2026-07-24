<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user && $user->is_admin) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Access Denied: Super Admin privileges required.',
        ], 403);
    }
}