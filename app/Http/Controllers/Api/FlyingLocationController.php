<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlyingLocation;
use Illuminate\Http\Request;

class FlyingLocationController extends Controller
{
    public function index()
    {
        $locations = FlyingLocation::with(['sports', 'qrCode'])
            ->with(['clearanceStatuses' => function($q) {
                $q->latest();
            }])
            ->withCount(['airspaceSessions as active_sessions_count' => function($q) {
                $q->where('status', 'active')
                  ->whereNull('checked_out_at')
                  ->where('expires_at', '>', now());
            }])
            ->get();

   return response()->json(['data' => $locations]);
    }

public function show($slug)
{
    $location = FlyingLocation::with([
        'sports',
        'clearanceStatuses' => fn($q) => $q->latest(),
        'qrCode',
        // Load active sessions with pilot details for the "Live Airspace" sidebar
        'airspaceSessions' => function($query) {
            $query->where('status', 'active')
                  ->where('expires_at', '>', now())
                  ->with('pilot.pilotProfile'); 
        }
    ])
    ->where('slug', $slug)
    ->firstOrFail();

    return response()->json(['data' => $location]);
}
}