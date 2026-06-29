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

    \Log::info('===== CHECK IN START =====');
    \Log::info('User ID', [
        'user_id' => optional($request->user())->id,
    ]);

    \Log::info('QR Token', [
        'token' => $request->token,
    ]);

    // Find QR Code
    $qr = \App\Models\QRCode::where('token', $request->token)->first();

    if (!$qr) {
        \Log::error('QR code not found');

        return response()->json([
            'message' => 'Invalid QR Code.'
        ], 404);
    }

    \Log::info('QR Found', [
        'qr_id' => $qr->id,
        'location_id' => $qr->flying_location_id,
    ]);

    // Get Location
    $location = $qr->location;

    if (!$location) {
        \Log::error('Location not found');

        return response()->json([
            'message' => 'Flying location not found.'
        ], 404);
    }

    \Log::info('Location', [
        'id' => $location->id,
        'name' => $location->name,
    ]);

    // Latest Status
    $currentStatus = $location->latestStatus;

    \Log::info('Latest Status', [
        'status' => optional($currentStatus)->status,
        'status_id' => optional($currentStatus)->id,
    ]);

    if ($currentStatus) {

        if ($currentStatus->status === 'red') {
            return response()->json([
                'message' => 'This flying location is currently CLOSED.'
            ], 422);
        }

        if ($currentStatus->status === 'yellow') {
            return response()->json([
                'message' => 'This flying location is currently PENDING approval.'
            ], 422);
        }
    }

    // Logged in user
    $pilot = $request->user();

    if (!$pilot) {
        \Log::error('No authenticated user');

        return response()->json([
            'message' => 'Unauthenticated.'
        ], 401);
    }

    \Log::info('Pilot', [
        'id' => $pilot->id,
        'name' => $pilot->name,
    ]);

    if (!$pilot->pilotProfile) {
        \Log::error('Pilot profile missing');

        return response()->json([
            'message' => 'Only licensed pilots can reserve airspace.'
        ], 403);
    }

    // Already checked in?
    $active = AirspaceSession::where('pilot_id', $pilot->id)
        ->where('status', 'active')
        ->whereNull('checked_out_at')
        ->where('expires_at', '>', now())
        ->exists();

    \Log::info('Already Active?', [
        'active' => $active,
    ]);

    if ($active) {
        return response()->json([
            'message' => 'You are already checked in at a location.'
        ], 422);
    }

    // Create session
    $session = AirspaceSession::create([
        'flying_location_id' => $location->id,
        'pilot_id' => $pilot->id,
        'checked_in_at' => now(),
        'expires_at' => now()->addHours(3),
        'status' => 'active',
    ]);

    \Log::info('Session Created', [
        'session_id' => $session->id,
    ]);

    \Log::info('===== CHECK IN END =====');

    return response()->json(
        $session->load('location')
    );
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