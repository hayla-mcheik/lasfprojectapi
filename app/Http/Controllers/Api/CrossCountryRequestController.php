<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrossCountryRequest;
use App\Models\CrossCountryRequestLocation;
use App\Models\FlyingLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\QRCode;
use Illuminate\Support\Str;
class CrossCountryRequestController extends Controller
{
    /**
     * Create Cross Country Request
     */
    /**
 * My Cross Country Requests
 */
public function index(Request $request)
{
    $requests = CrossCountryRequest::with([

        'locations.location',

    ])
    ->where('pilot_id', $request->user()->id)
    ->latest()
    ->get();

    return response()->json([
        'requests' => $requests,
    ]);
}
public function store(Request $request)
{
    $request->validate([

        'flight_date' => 'required|date',

        'takeoff_time' => 'required',

        'estimated_landing_time' => 'required',

        'notes' => 'nullable|string',

        'locations' => 'required|array|min:2',

        'locations.*' => 'exists:flying_locations,id',

    ]);
$existingRequest = CrossCountryRequest::where('pilot_id', $request->user()->id)
    ->whereIn('status', [
        'pending',
        'approved',
        'open',
    ])
    ->first();

if ($existingRequest) {

    return response()->json([
        'message' => 'You already have an active Cross Country request.'
    ], 422);

}
    DB::beginTransaction();

    try {

        $crossCountry = CrossCountryRequest::create([

            'pilot_id' => $request->user()->id,

            'flight_date' => $request->flight_date,

            'takeoff_time' => $request->takeoff_time,

            'estimated_landing_time' => $request->estimated_landing_time,

            'status' => 'pending',

            'notes' => $request->notes,

        ]);

        foreach ($request->locations as $index => $locationId) {

            CrossCountryRequestLocation::create([

                'cross_country_request_id' => $crossCountry->id,

                'flying_location_id' => $locationId,

                'route_order' => $index + 1,

            ]);

        }

        DB::commit();

        return response()->json([

            'message' => 'Cross Country request submitted successfully.',

            'request' => $crossCountry->load([

                'pilot',

                'locations.location',

            ]),

        ], 201);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::error('Cross Country Request Error', [

            'message' => $e->getMessage(),

        ]);

        return response()->json([

            'message' => 'Unable to create Cross Country request.',

        ], 500);

    }
}

/**
 * View Cross Country Request
 */
public function show(Request $request, CrossCountryRequest $crossCountryRequest)
{
    if ($crossCountryRequest->pilot_id !== $request->user()->id) {

        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);

    }

$crossCountryRequest->load([

    'pilot',

    'locations.location',

    'qrCode',

    'activeSession.currentLocation',
     'session.currentLocation',

]);

return response()->json([
    'request' => $crossCountryRequest,
]);
}
/**
 * Cancel Cross Country Request
 */
public function cancel(Request $request, CrossCountryRequest $crossCountryRequest)
{
    if ($crossCountryRequest->pilot_id !== $request->user()->id) {

        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);

    }

    if ($crossCountryRequest->status !== 'pending') {

        return response()->json([
            'message' => 'Only pending requests can be cancelled.'
        ], 422);

    }

    $crossCountryRequest->update([
        'status' => 'cancelled',
    ]);

    return response()->json([
        'message' => 'Cross Country request cancelled successfully.',
    ]);
}
/**
 * Admin - List All Cross Country Requests
 */
public function adminIndex(Request $request)
{
    $user = $request->user();

    if (!$user->isAdmin()) {

        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);

    }

$requests = CrossCountryRequest::with([

    'pilot.pilotProfile',

    'locations.location',

])
->latest()
->get();

    return response()->json([
        'requests' => $requests,
    ]);
}

/**
 * Admin - Show Cross Country Request
 */
public function adminShow(Request $request, CrossCountryRequest $crossCountryRequest)
{
    if (!$request->user()->isAdmin()) {

        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);

    }

    $crossCountryRequest->load([

        'pilot.pilotProfile',

        'locations.location',

        'qrCode',

        'session.currentLocation',

    ]);

    return response()->json([
        'request' => $crossCountryRequest,
    ]);
}
/**
 * Admin - Update Cross Country Request Status
 */
public function updateStatus(Request $request, CrossCountryRequest $crossCountryRequest)
{
    $user = $request->user();

    if (!$user->isAdmin()) {

        return response()->json([
            'message' => 'Unauthorized.'
        ], 403);

    }

    $request->validate([

        'status' => 'required|in:closed,pending,open',

    ]);

$crossCountryRequest->update([

    'status' => $request->status,

]);

/*
|--------------------------------------------------------------------------
| Generate Cross Country QR
|--------------------------------------------------------------------------
|
| Generate a QR only the first time the request becomes OPEN.
|
*/

if (
    $crossCountryRequest->status === 'open' &&
    !$crossCountryRequest->qrCode
) {

    QRCode::create([

        'token' => (string) Str::uuid(),

        'type' => 'cross_country',

        'cross_country_request_id' => $crossCountryRequest->id,

    ]);

}

$crossCountryRequest->load([

    'pilot',

    'locations.location',

    'qrCode',

]);

return response()->json([

    'message' => 'Cross Country request status updated successfully.',

    'request' => $crossCountryRequest,

]);
}
public function history(Request $request)
{
    $requests = CrossCountryRequest::with([
        'locations.location',
    ])
    ->where('pilot_id', $request->user()->id)
    ->whereIn('status', [
        'closed',
        'cancelled',
    ])
    ->latest()
    ->get();

    return response()->json([
        'requests' => $requests,
    ]);
}

}