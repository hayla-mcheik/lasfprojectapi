<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AirspaceSession;

class CheckPilotFlying
{
    public function handle(Request $request, Closure $next)
    {
        $pilotId = $request->user()->id;
        
        $activeSession = AirspaceSession::where('pilot_id', $pilotId)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->first();
        
        if ($activeSession) {
            return response()->json([
                'message' => 'You are already checked in at another location',
                'current_session' => $activeSession
            ], 403);
        }
        
        return $next($request);
    }
}