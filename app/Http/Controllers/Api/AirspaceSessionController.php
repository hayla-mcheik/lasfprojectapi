<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirspaceSession;
use App\Models\QRCode;
use Illuminate\Http\Request;

class AirspaceSessionController extends Controller
{
    /**
     * PUBLIC: Get active pilots for a specific location.
     * Nuxt uses this to show the "Live Airspace" sidebar.
     */
    public function active(Request $request)
    {
        $locationId = $request->query('location_id');

        if (!$locationId) return response()->json([]);

        $sessions = AirspaceSession::with(['pilot.pilotProfile'])
            ->where('flying_location_id', $locationId)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->get();

        return response()->json($sessions);
    }

    /**
     * PRIVATE: Pilot check-in.
     * Prevents non-pilots from checking in and prevents double sessions.
     */
    public function store(Request $request)
    {
        $request->validate(['token' => 'required|exists:q_r_codes,token']);

        // 1. Only Pilots can check in
        if (!$request->user()->pilotProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not registered as a Pilot profile.'
            ], 403);
        }

        $qr = QRCode::with('location')->where('token', $request->token)->firstOrFail();

        // 2. Prevent multiple active sessions
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

        return response()->json($session->load('pilot.pilotProfile', 'location'));
    }

    /**
     * PRIVATE: Get the specific user's current session if any.
     */
    public function userActiveSession(Request $request)
    {
        $session = AirspaceSession::with('location')
            ->where('pilot_id', $request->user()->id)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->first();

        return response()->json($session);
    }

    /**
     * PRIVATE: Check-out (Landing).
     */
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