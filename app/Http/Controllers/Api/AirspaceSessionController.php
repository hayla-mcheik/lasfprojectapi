<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirspaceSession;
use App\Models\QRCode;
use Illuminate\Http\Request;

class AirspaceSessionController extends Controller
{
    // 1. Get location info from QR
    public function qr($token)
    {
        $qr = QRCode::with('location')->where('token', $token)->firstOrFail();
        return response()->json($qr->location);
    }

    // 2. Pilot check-in (requires auth:sanctum middleware in routes)
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:q_r_codes,token',
        ]);

        // Ensure user has a pilot profile
        if (!$request->user()->pilotProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not registered as a Pilot.'
            ], 403);
        }

        $qr = QRCode::with('location')->where('token', $request->token)->firstOrFail();
        
        // Prevent double check-in
        $activeElsewhere = AirspaceSession::where('pilot_id', $request->user()->id)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($activeElsewhere) {
            return response()->json([
                'success' => false, 
                'message' => 'You are already checked in at another location.'
            ], 403);
        }

        $session = AirspaceSession::create([
            'flying_location_id' => $qr->location->id,
            'pilot_id' => $request->user()->id,
            'checked_in_at' => now(),
            'expires_at' => now()->addHours(2),
            'status' => 'active',
        ]);

        return response()->json($session->load('pilot', 'location'));
    }

    // 3. Public: Get active pilots for a location
    public function active(Request $request)
    {
        $locationId = $request->query('location_id');

        if (!$locationId) {
            return response()->json([]);
        }

        $sessions = AirspaceSession::with('pilot')
            ->where('flying_location_id', $locationId)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->get();

        return response()->json($sessions);
    }

    // 4. Pilot check-out
    public function checkout(Request $request, $id)
    {
        $session = AirspaceSession::where('id', $id)
            ->where('pilot_id', $request->user()->id)
            ->firstOrFail();

        $session->update([
            'checked_out_at' => now(),
            'status' => 'closed',
        ]);

        return response()->json($session);
    }
}