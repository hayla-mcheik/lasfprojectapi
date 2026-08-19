<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirspaceSession;
use App\Models\ClearanceStatus;
use App\Models\QRCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\CrossCountryRequest;
use App\Models\CrossCountryQRCode;
class AirspaceSessionController extends Controller
{
    /**
     * PUBLIC:
     * Get active pilots for a specific flying location.
     *
     * Used by Nuxt to show the Live Airspace section.
     */
    public function active(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Location ID
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'location_id' => 'required|integer|exists:flying_locations,id',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Expire Old Sessions
        |--------------------------------------------------------------------------
        |
        | Any session whose expiration time has passed should no longer remain
        | marked as active.
        |
        */

        AirspaceSession::where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Get Active Sessions
        |--------------------------------------------------------------------------
        */

        $sessions = AirspaceSession::with([
                'pilot.pilotProfile',
                'location',
            ])
            ->where('flying_location_id', $request->location_id)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->latest('checked_in_at')
            ->get();

        return response()->json($sessions);
    }

/**
 * PUBLIC:
 * Resolve QR Code
 *
 * Used by the mobile application after scanning a QR.
 */
public function qr($token)
{
    /*
    |--------------------------------------------------------------------------
    | Airspace QR
    |--------------------------------------------------------------------------
    */

    $airspaceQR = QRCode::with('location')
        ->where('token', $token)
        ->first();

    if ($airspaceQR) {

        return response()->json([

            'type' => 'airspace',

            'token' => $airspaceQR->token,

            'location' => $airspaceQR->location,

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Cross Country QR
    |--------------------------------------------------------------------------
    */

    $crossCountryQR = CrossCountryQRCode::with([
        'crossCountryRequest.locations.location',
    ])
    ->where('token', $token)
    ->first();

    if ($crossCountryQR) {

        return response()->json([

            'type' => 'cross_country',

            'token' => $crossCountryQR->token,

            'request' => $crossCountryQR->crossCountryRequest,

        ]);

    }

    return response()->json([

        'message' => 'Invalid QR Code.',

    ], 404);
}

    /**
     * PRIVATE:
     * Pilot Check-In.
     *
     * Rules:
     *
     * 1. User must be authenticated.
     * 2. User must have a pilot profile.
     * 3. QR code must exist.
     * 4. Flying location must exist.
     * 5. Flying location must have a permission record for TODAY.
     * 6. Today's permission must be GREEN.
     * 7. Pilot cannot already have another active flying session.
     * 8. Create a new 3-hour flying session.
     */
    public function store(Request $request)
    {
        Log::info('STORE AIRSPACE SESSION CALLED', [
    'user_id' => optional($request->user())->id,
    'token' => $request->token,
]);
        /*
        |--------------------------------------------------------------------------
        | Validate Request
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'token' => 'required|string',
        ]);


        Log::info('===== CHECK IN START =====', [
            'user_id' => optional($request->user())->id,
            'token' => $request->token,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Authenticated Pilot
        |--------------------------------------------------------------------------
        */

        $pilot = $request->user();


        if (!$pilot) {

            Log::warning('Check-in rejected: unauthenticated user.');

            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }


        Log::info('Authenticated Pilot', [
            'pilot_id' => $pilot->id,
            'pilot_name' => $pilot->name,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Licensed Pilot Check
        |--------------------------------------------------------------------------
        */

        if (!$pilot->pilotProfile) {

            Log::warning('Check-in rejected: pilot profile missing.', [
                'pilot_id' => $pilot->id,
            ]);

            return response()->json([
                'message' => 'Only licensed pilots can check in to fly.',
            ], 403);
        }

/*
|--------------------------------------------------------------------------
| Pilot Ban Check
|--------------------------------------------------------------------------
*/

if ($pilot->pilotProfile->isCurrentlyBanned()) {

    Log::warning('Check-in rejected: pilot is banned.', [

        'pilot_id' => $pilot->id,

        'ban_until' => $pilot->pilotProfile->ban_until,

        'reason' => $pilot->pilotProfile->ban_reason,

    ]);

    return response()->json([

        'success' => false,
'message' =>
    'You are banned from flying until '
    . optional($pilot->pilotProfile->ban_until)->format('d/m/Y')
    . '. Reason: '
    . ($pilot->pilotProfile->ban_reason ?: 'No reason provided.'),

        'ban_until' => optional($pilot->pilotProfile->ban_until)
            ? $pilot->pilotProfile->ban_until->format('Y-m-d')
            : null,

        'reason' => $pilot->pilotProfile->ban_reason,

    ], 403);
}

        /*
        |--------------------------------------------------------------------------
        | Find QR Code
        |--------------------------------------------------------------------------
        */

        $qr = QRCode::with('location')
            ->where('token', $request->token)
            ->first();


        if (!$qr) {

            Log::warning('Check-in rejected: invalid QR code.', [
                'token' => $request->token,
            ]);

            return response()->json([
                'message' => 'Invalid QR Code.',
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Get Flying Location
        |--------------------------------------------------------------------------
        */

        $location = $qr->location;


        if (!$location) {

            Log::error('Check-in rejected: flying location missing.', [
                'qr_id' => $qr->id,
            ]);

            return response()->json([
                'message' => 'Flying location not found.',
            ], 404);
        }


        Log::info('Flying Location Found', [
            'location_id' => $location->id,
            'location_name' => $location->name,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Check Whether Location Is Enabled
        |--------------------------------------------------------------------------
        */

        if (!$location->is_enabled) {

            Log::warning('Check-in rejected: location disabled.', [
                'location_id' => $location->id,
            ]);

            return response()->json([
                'message' => 'This flying location is currently unavailable.',
            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Get Today's Permission
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | We DO NOT use:
        |
        |     $location->latestStatus
        |
        | because the latest database record could belong to tomorrow,
        | yesterday, or another date.
        |
        | Instead, we explicitly find the permission for TODAY.
        |
        */

        $today = today()->toDateString();


        $currentStatus = ClearanceStatus::query()

            ->where(
                'flying_location_id',
                $location->id
            )

            ->whereDate(
                'permission_date',
                $today
            )

            ->first();


        Log::info('Today Flying Permission', [

            'location_id' => $location->id,

            'permission_date' => $today,

            'status_id' => optional($currentStatus)->id,

            'status' => optional($currentStatus)->status,

        ]);


        /*
        |--------------------------------------------------------------------------
        | No Permission Record Today = CLOSED
        |--------------------------------------------------------------------------
        |
        | Client Requirement:
        |
        | "When no request is sent just have it as closed."
        |
        */

        if (!$currentStatus) {

            Log::warning('Check-in rejected: no permission record today.', [

                'pilot_id' => $pilot->id,

                'location_id' => $location->id,

                'permission_date' => $today,

            ]);


            return response()->json([

                'message' =>
                    'This flying location is CLOSED today because no flight permission exists.',

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | RED = CLOSED
        |--------------------------------------------------------------------------
        */

        if ($currentStatus->status === 'red') {

            Log::warning('Check-in rejected: location closed.', [

                'pilot_id' => $pilot->id,

                'location_id' => $location->id,

                'permission_date' => $today,

            ]);


            return response()->json([

                'message' =>
                    'This flying location is CLOSED today.',

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | YELLOW = PENDING
        |--------------------------------------------------------------------------
        */

        if ($currentStatus->status === 'yellow') {

            Log::warning('Check-in rejected: permission pending.', [

                'pilot_id' => $pilot->id,

                'location_id' => $location->id,

                'permission_date' => $today,

            ]);


            return response()->json([

                'message' =>
                    'Permission for this flying location is still PENDING.',

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Only GREEN Can Continue
        |--------------------------------------------------------------------------
        */

        if ($currentStatus->status !== 'green') {

            Log::warning('Check-in rejected: unsupported permission status.', [

                'pilot_id' => $pilot->id,

                'location_id' => $location->id,

                'status' => $currentStatus->status,

            ]);


            return response()->json([

                'message' =>
                    'Check-in is not allowed for this flying location.',

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Expire Pilot's Old Sessions
        |--------------------------------------------------------------------------
        |
        | Before checking for an existing active session, clean expired sessions
        | belonging to this pilot.
        |
        */

        AirspaceSession::where('pilot_id', $pilot->id)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Prevent Multiple Active Sessions
        |--------------------------------------------------------------------------
        */

        $activeSession = AirspaceSession::with('location')

            ->where('pilot_id', $pilot->id)

            ->where('status', 'active')

            ->whereNull('checked_out_at')

            ->where('expires_at', '>', now())

            ->first();


        if ($activeSession) {

            Log::warning('Check-in rejected: pilot already flying.', [

                'pilot_id' => $pilot->id,

                'active_session_id' => $activeSession->id,

                'active_location_id' =>
                    $activeSession->flying_location_id,

            ]);


            return response()->json([

                'message' =>
                    'You are already checked in at ' .
                    optional($activeSession->location)->name .
                    '. Please check out before starting another flying session.',

                'active_session' => $activeSession,

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Flying Session
        |--------------------------------------------------------------------------
        */

        $session = AirspaceSession::create([

            'flying_location_id' =>
                $location->id,

            'pilot_id' =>
                $pilot->id,

            'checked_in_at' =>
                now(),

            'checked_out_at' =>
                null,

            'expires_at' =>
                now()->addHours(7),

            'status' =>
                'active',

        ]);


        Log::info('Flying Session Created', [

            'session_id' => $session->id,

            'pilot_id' => $pilot->id,

            'location_id' => $location->id,

            'permission_date' => $today,

        ]);


        Log::info('===== CHECK IN END =====');


        /*
        |--------------------------------------------------------------------------
        | Return Session
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'message' => 'Check-in successful.',

            'session' => $session->load([

                'location',

                'pilot.pilotProfile',

            ]),

        ], 201);
    }


    /**
     * PRIVATE:
     * Get Logged-In Pilot's Current Active Session.
     */
    public function userActiveSession(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Expire Old Sessions
        |--------------------------------------------------------------------------
        */

        AirspaceSession::where('pilot_id', $request->user()->id)
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Find Current Active Session
        |--------------------------------------------------------------------------
        */

 $session = AirspaceSession::with('location')
    ->where('pilot_id', $request->user()->id)
    ->whereIn('status', ['active', 'paused'])
    ->whereNull('checked_out_at')
    ->where('expires_at', '>', now())
    ->latest('checked_in_at')
    ->first();


Log::info('ACTIVE SESSION API CALLED', [
    'user_id' => $request->user()->id,
    'session_id' => optional($session)->id,
    'location_id' => optional($session)->flying_location_id,
]);
Log::info('ACTIVE SESSION RESULT', [
    'session' => $session,
    'is_null' => is_null($session),
]);
        return response()->json($session);
    }

public function pause(
    Request $request,
    AirspaceSession $session
) {
    \Log::info('PAUSE', [
        'route_session_id' => $session->id,
        'pilot_id' => $session->pilot_id,
        'authenticated_user' => $request->user()->id,
    ]);

    if ($session->pilot_id != $request->user()->id) {
        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);
    }

    $session->update([
        'status' => 'paused'
    ]);

    return response()->json([
        'message' => 'Permission paused successfully.',
        'session' => $session
    ]);
}
// In your AirspaceSessionController.php
public function resume(Request $request, AirspaceSession $session)
{
    // Log everything
    \Log::info('===== RESUME CALLED =====', [
        'session_id' => $session->id,
        'pilot_id' => $request->user()->id,
        'method' => $request->method(),
        'all_headers' => $request->headers->all(),
        'session_status' => $session->status,
    ]);

    if ($session->pilot_id != $request->user()->id) {
        \Log::warning('RESUME UNAUTHORIZED', [
            'session_pilot' => $session->pilot_id,
            'request_user' => $request->user()->id,
        ]);
        
        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);
    }

    $session->update([
        'status' => 'active'
    ]);

    \Log::info('RESUME SUCCESS', [
        'session_id' => $session->id,
        'new_status' => $session->status,
    ]);

    return response()->json([
        'message' => 'Permission resumed successfully.',
        'session' => $session
    ]);
}

    /**
     * PRIVATE:
     * Pilot Check-Out / Landing.
     */
    public function checkout(Request $request, $id)
    {
        Log::info('===== CHECK OUT START =====', [

            'session_id' => $id,

            'pilot_id' => optional($request->user())->id,

        ]);


        /*
        |--------------------------------------------------------------------------
        | Find Pilot Session
        |--------------------------------------------------------------------------
        */

        $session = AirspaceSession::where('id', $id)

            ->where(
                'pilot_id',
                $request->user()->id
            )

            ->first();


        if (!$session) {

            Log::warning('Checkout rejected: session not found.', [

                'session_id' => $id,

                'pilot_id' => $request->user()->id,

            ]);


            return response()->json([

                'message' => 'Session not found.',

            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Checkout
        |--------------------------------------------------------------------------
        */

        if ($session->checked_out_at !== null) {

            return response()->json([

                'message' =>
                    'This flying session has already been checked out.',

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Checkout Of Expired Session
        |--------------------------------------------------------------------------
        */

        if ($session->status === 'expired') {

            return response()->json([

                'message' =>
                    'This flying session has already expired.',

            ], 422);
        }


        /*
        |--------------------------------------------------------------------------
        | Close Session
        |--------------------------------------------------------------------------
        */

        $session->update([

            'status' => 'closed',

            'checked_out_at' => now(),

        ]);


        $session->refresh();


        Log::info('Flying Session Closed', [

            'session_id' => $session->id,

            'pilot_id' => $request->user()->id,

            'checked_out_at' => $session->checked_out_at,

        ]);


        Log::info('===== CHECK OUT END =====');


        return response()->json([

            'message' => 'Check-out successful.',

            'session' => $session->load('location'),

        ]);
    }
}