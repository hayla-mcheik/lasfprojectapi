<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirspaceSession;
use App\Models\PilotLocation;
use Illuminate\Http\Request;

class PilotLocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        $session = AirspaceSession::where('pilot_id', auth()->id())
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'No active flight session'
            ], 422);
        }

        $location = PilotLocation::create([
            'pilot_id' => auth()->id(),
            'airspace_session_id' => $session->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
        ]);

        return response()->json($location);
    }

    public function live($locationId)
    {
        $sessions = AirspaceSession::with([
            'pilot',
            'locations' => function ($q) {
                $q->latest()->limit(1);
            }
        ])
        ->where('flying_location_id', $locationId)
  ->where('status', 'active')
->whereNull('checked_out_at')
->where('expires_at', '>', now())
->get();
        return response()->json($sessions);
    }
}