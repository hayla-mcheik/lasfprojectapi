<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirspaceSession;
use App\Models\FlyingLocation;
use App\Models\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AirspaceSessionController extends Controller
{
    // QR scan → return location info
    public function qr($token)
    {
        $qr = QRCode::with('location')->where('token', $token)->firstOrFail();
        return response()->json($qr->location);
    }

    // Pilot check-in
// App\Http\Controllers\Api\AirspaceSessionController.php

public function store(Request $request)
{
    $request->validate([
        'token' => 'required|exists:q_r_codes,token',
    ]);

    // 1. Ensure user has a pilot profile
    if (!$request->user()->pilotProfile) {
        return response()->json([
            'success' => false,
            'message' => 'Your account is not registered as a Pilot.'
        ], 403);
    }

    $qr = QRCode::with('location')->where('token', $request->token)->firstOrFail();
    $location = $qr->location;

    // 2. Prevent double check-in
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

    // 3. Create Session
    $session = AirspaceSession::create([
        'flying_location_id' => $location->id,
        'pilot_id' => $request->user()->id,
        'checked_in_at' => now(),
        'expires_at' => now()->addHours(2),
        'status' => 'active',
    ]);

    return response()->json($session->load('pilot.pilotProfile', 'location'));
}
// App\Http\Controllers\Api\AirspaceSessionController.php

// app/Http/Controllers/Api/AirspaceSessionController.php

public function active(Request $request)
{
    $locationId = $request->query('location_id');

    $sessions = AirspaceSession::with('pilot.pilotProfile')
        ->where('flying_location_id', $locationId)
        ->where('status', 'active')
        ->whereNull('checked_out_at') // MUST be null to be active
        ->where('expires_at', '>', now())
        ->get();

    return response()->json($sessions);
}

public function checkout(Request $request, $id)
{
    $session = AirspaceSession::where('id', $id)->where('pilot_id', $request->user()->id)->firstOrFail();
    $session->update([
        'checked_out_at' => now(),
        'status' => 'closed', // Logic: 'active' means flying, 'closed' means landed
    ]);
    return response()->json($session);
}
// app/Http/Controllers/Api/AirspaceSessionController.php

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
}
