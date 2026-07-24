<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirspaceSession;
use App\Models\PilotLocation;
use Illuminate\Http\Request;
use App\Models\CrossCountrySession;
use App\Models\FlyingLocation;
class PilotLocationController extends Controller
{
  public function update(Request $request)
{
    $request->validate([
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'accuracy' => 'nullable|numeric',
    ]);

    /*
    |--------------------------------------------------------------------------
    | Look for an active Airspace session
    |--------------------------------------------------------------------------
    */

    $session = AirspaceSession::where('pilot_id', auth()->id())
        ->where('status', 'active')
        ->whereNull('checked_out_at')
        ->where('expires_at', '>', now())
        ->first();

    /*
    |--------------------------------------------------------------------------
    | If no Airspace session exists, look for an active Cross Country session
    |--------------------------------------------------------------------------
    */

    $crossCountrySession = null;

    if (!$session) {

        $crossCountrySession = CrossCountrySession::where(
            'pilot_id',
            auth()->id()
        )
        ->where('is_active', true)
        ->first();

    }

    /*
    |--------------------------------------------------------------------------
    | No active flight at all
    |--------------------------------------------------------------------------
    */

    if (!$session && !$crossCountrySession) {

        return response()->json([
            'message' => 'No active flight session.'
        ], 422);

    }

    /*
    |--------------------------------------------------------------------------
    | Save GPS
    |--------------------------------------------------------------------------
    */

    $location = PilotLocation::create([

        'pilot_id' => auth()->id(),

        'airspace_session_id' => $session?->id,

        'cross_country_session_id' => $crossCountrySession?->id,

        'latitude' => $request->latitude,

        'longitude' => $request->longitude,

        'accuracy' => $request->accuracy,

    ]);

    /*
|--------------------------------------------------------------------------
| Update Current XC Location
|--------------------------------------------------------------------------
*/

if ($crossCountrySession) {

    $currentLocation = $this->findCurrentFlyingLocation(
        $request->latitude,
        $request->longitude
    );

    if (
        $currentLocation &&
        $crossCountrySession->current_location_id !== $currentLocation->id
    ) {

        $crossCountrySession->update([
            'current_location_id' => $currentLocation->id,
        ]);

        // Keep the model in sync after updating
        $crossCountrySession->refresh();
    }

}
    return response()->json($location);
}

    /**
     * All active pilots
     */
public function liveAll()
{
    /*
    |--------------------------------------------------------------------------
    | Active Airspace Sessions
    |--------------------------------------------------------------------------
    */

    $airspaceSessions = AirspaceSession::with([

        'pilot',

        'location',

        'locations' => function ($q) {

            $q->latest()->limit(1);

        },

    ])
    ->where('status', 'active')
    ->whereNull('checked_out_at')
    ->where('expires_at', '>', now())
    ->get()
    ->map(function ($session) {

        return [

            'id' => $session->id,

            'type' => 'airspace',

            'pilot' => $session->pilot,

            'location' => $session->location,

            'gps' => $session->locations->first(),

            'started_at' => $session->checked_in_at,

        ];

    });

    /*
    |--------------------------------------------------------------------------
    | Active Cross Country Sessions
    |--------------------------------------------------------------------------
    */

    $crossCountrySessions = CrossCountrySession::with([

        'pilot',

        'currentLocation',

        'locations' => function ($q) {

            $q->latest()->limit(1);

        },

    ])
    ->where('is_active', true)
    ->get()
    ->map(function ($session) {

        return [

            'id' => $session->id,

            'type' => 'cross_country',

            'pilot' => $session->pilot,

            'location' => $session->currentLocation,

            'gps' => $session->locations->first(),

            'started_at' => $session->started_at,

        ];

    });

    return response()->json(

        $airspaceSessions
            ->concat($crossCountrySessions)
            ->values()

    );
}
    /**
     * Active pilots for one location
     */
public function live($locationId)
{
    /*
    |--------------------------------------------------------------------------
    | Airspace
    |--------------------------------------------------------------------------
    */

    $airspaceSessions = AirspaceSession::with([

        'pilot',

        'location',

        'locations' => function ($q) {

            $q->latest()->limit(1);

        },

    ])
    ->where('flying_location_id', $locationId)
    ->where('status', 'active')
    ->whereNull('checked_out_at')
    ->where('expires_at', '>', now())
    ->get()
    ->map(function ($session) {

        return [

            'id' => $session->id,

            'type' => 'airspace',

            'pilot' => $session->pilot,

            'location' => $session->location,

            'gps' => $session->locations->first(),

            'started_at' => $session->checked_in_at,

        ];

    });

    /*
    |--------------------------------------------------------------------------
    | Cross Country
    |--------------------------------------------------------------------------
    */

    $crossCountrySessions = CrossCountrySession::with([

        'pilot',

        'currentLocation',

        'locations' => function ($q) {

            $q->latest()->limit(1);

        },

    ])
    ->where('current_location_id', $locationId)
    ->where('is_active', true)
    ->get()
    ->map(function ($session) {

        return [

            'id' => $session->id,

            'type' => 'cross_country',

            'pilot' => $session->pilot,

            'location' => $session->currentLocation,

            'gps' => $session->locations->first(),

            'started_at' => $session->started_at,

        ];

    });

    return response()->json(

        $airspaceSessions
            ->concat($crossCountrySessions)
            ->values()

    );
}
private function findCurrentFlyingLocation(
    float $latitude,
    float $longitude
): ?FlyingLocation
{
    $nearest = null;

    $nearestDistance = PHP_FLOAT_MAX;

    foreach (FlyingLocation::all() as $location) {

        $distance = $this->distance(

            $latitude,
            $longitude,

            $location->latitude,
            $location->longitude

        );

        if ($distance < $nearestDistance) {

            $nearestDistance = $distance;

            $nearest = $location;

        }

    }

    // Only consider locations within 5 km
    if ($nearestDistance > 5) {
        return null;
    }

    return $nearest;
}
private function distance(
    float $lat1,
    float $lon1,
    float $lat2,
    float $lon2
): float
{
    $earthRadius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a =
        sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) *
        cos(deg2rad($lat2)) *
        sin($dLon / 2) *
        sin($dLon / 2);

    $c = 2 * atan2(
        sqrt($a),
        sqrt(1 - $a)
    );

    return $earthRadius * $c;
}

private function pointInPolygon(
    float $latitude,
    float $longitude,
    array $polygon
): bool
{
    $inside = false;

    $count = count($polygon);

    if ($count < 3) {
        return false;
    }

    for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {

        $latI = $polygon[$i]['lat'];
        $lngI = $polygon[$i]['lng'];

        $latJ = $polygon[$j]['lat'];
        $lngJ = $polygon[$j]['lng'];

        if (
            (($lngI > $longitude) != ($lngJ > $longitude)) &&
            (
                $latitude <
                ($latJ - $latI) *
                ($longitude - $lngI) /
                (($lngJ - $lngI) ?: 0.0000001)
                + $latI
            )
        ) {
            $inside = !$inside;
        }
    }

    return $inside;
}
}