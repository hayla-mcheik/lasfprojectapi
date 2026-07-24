<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrossCountryRequest;
use App\Models\CrossCountrySession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AirspaceSession;

class CrossCountrySessionController extends Controller
{
    /**
     * Start Cross Country Session
     */
public function start(
    Request $request,
    CrossCountryRequest $crossCountryRequest
)
{
    $pilot = $request->user();

    /*
    |--------------------------------------------------------------------------
    | Owner Check
    |--------------------------------------------------------------------------
    */

    if ($crossCountryRequest->pilot_id != $pilot->id) {

        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);

    }

    /*
    |--------------------------------------------------------------------------
    | Request Must Be Open
    |--------------------------------------------------------------------------
    */

    if ($crossCountryRequest->status !== 'open') {

        return response()->json([
            'message' => 'This Cross Country request is not approved.'
        ], 422);

    }

    /*
    |--------------------------------------------------------------------------
    | Session Already Started
    |--------------------------------------------------------------------------
    */

$activeSession = CrossCountrySession::where(

    'cross_country_request_id',
    $crossCountryRequest->id

)
->where('is_active', true)
->first();

if ($activeSession) {

    return response()->json([

        'message' => 'Cross Country session already active.',

    ], 422);

}

    /*
    |--------------------------------------------------------------------------
    | Cannot Have Active Airspace Session
    |--------------------------------------------------------------------------
    */

    $activeAirspace = AirspaceSession::where('pilot_id', $pilot->id)
        ->where('status', 'active')
        ->whereNull('checked_out_at')
        ->where('expires_at', '>', now())
        ->exists();

    if ($activeAirspace) {

        return response()->json([
            'message' => 'Please finish your current Airspace session first.'
        ], 422);

    }

    /*
    |--------------------------------------------------------------------------
    | First Location
    |--------------------------------------------------------------------------
    */

    $firstLocation = $crossCountryRequest
        ->locations()
        ->orderBy('route_order')
        ->first();

    if (!$firstLocation) {

        return response()->json([
            'message' => 'No route locations found.'
        ], 422);

    }

    /*
    |--------------------------------------------------------------------------
    | Create Session
    |--------------------------------------------------------------------------
    */

    $session = CrossCountrySession::create([

        'cross_country_request_id' => $crossCountryRequest->id,

        'pilot_id' => $pilot->id,

        'current_location_id' => $firstLocation->flying_location_id,

        'started_at' => now(),

        'is_active' => true,

    ]);

    return response()->json([

        'message' => 'Cross Country session started successfully.',

        'session' => $session->load([
            'currentLocation',
            'request.locations.location',
        ]),

    ], 201);
}

public function finish(
    Request $request,
    CrossCountrySession $crossCountrySession
)
{
    /*
    |--------------------------------------------------------------------------
    | Owner Check
    |--------------------------------------------------------------------------
    */

    if ($crossCountrySession->pilot_id != $request->user()->id) {

        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);

    }

    /*
    |--------------------------------------------------------------------------
    | Already Finished
    |--------------------------------------------------------------------------
    */

    if (!$crossCountrySession->is_active) {

        return response()->json([
            'message' => 'This Cross Country session has already ended.'
        ], 422);

    }

    /*
    |--------------------------------------------------------------------------
    | Finish Session
    |--------------------------------------------------------------------------
    */

    $crossCountrySession->update([

        'is_active' => false,

        'ended_at' => now(),

    ]);
    $crossCountrySession->request()->update([

    'status' => 'closed',

]);

    /*
    |--------------------------------------------------------------------------
    | Refresh
    |--------------------------------------------------------------------------
    */

    $crossCountrySession->refresh();

    return response()->json([

        'message' => 'Cross Country session finished successfully.',

        'session' => $crossCountrySession->load([

            'currentLocation',

            'request.locations.location',

        ]),

    ]);

}
public function statistics(Request $request)
{
    $pilot = $request->user();

    $sessions = CrossCountrySession::where(
        'pilot_id',
        $pilot->id
    )
    ->whereNotNull('ended_at')
    ->get();

    return response()->json([

        'total_flights' => $sessions->count(),

        'total_hours' => round(

            $sessions->sum(function ($session) {

                return $session->started_at
                    ->diffInMinutes($session->ended_at);

            }) / 60,

            2

        ),

        'longest_flight' => $sessions->max(function ($session) {

            return $session->started_at
                ->diffInMinutes($session->ended_at);

        }),

    ]);
}
public function track(CrossCountrySession $crossCountrySession)
{
    return response()->json(

        $crossCountrySession->locations()
            ->orderBy('created_at')
            ->get([
                'latitude',
                'longitude',
                'created_at',
            ])

    );
}
}