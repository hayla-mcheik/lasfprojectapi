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
        // Eager load everything needed for the dashboard
        $location = FlyingLocation::with([
            'sports', 
            'clearanceStatuses', 
            'qrCode', 
            'airspaceSessions' => function($q) {
                $q->where('status', 'active')
                  ->whereNull('checked_out_at')
                  ->where('expires_at', '>', now())
                  ->with('pilot.pilotProfile'); // Load pilot names and license
            }
        ])
        ->where('slug', $slug)
        ->firstOrFail();

        // Wrap in 'data' key to match what Nuxt expects
        return response()->json(['data' => $location]);
    }
}