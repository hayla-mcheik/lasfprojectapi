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
    $request->validate([
        'token' => 'required|string',
    ]);

    // 1. Find the QR code and its associated location
    $qr = \App\Models\QRCode::where('token', $request->token)->first();

    if (!$qr) {
        return response()->json(['message' => 'Invalid or expired QR code.'], 404);
    }

    $pilot = $request->user();

    // 2. Security: Ensure user is a Pilot
    if (!$pilot->pilotProfile) {
        return response()->json(['message' => 'Only licensed pilots can reserve airspace.'], 403);
    }

    // 3. Prevent double check-in
    $active = AirspaceSession::where('pilot_id', $pilot->id)
        ->where('status', 'active')
        ->exists();

    if ($active) {
        return response()->json(['message' => 'You are already checked in at a location.'], 422);
    }

    // 4. Create Session
    $session = AirspaceSession::create([
        'flying_location_id' => $qr->flying_location_id,
        'pilot_id' => $pilot->id,
        'checked_in_at' => now(),
        'expires_at' => now()->addHours(3), // Standard 3-hour window
        'status' => 'active',
    ]);

    return response()->json($session->load('location'));
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