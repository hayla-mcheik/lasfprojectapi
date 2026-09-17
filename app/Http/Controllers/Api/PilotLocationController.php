<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AirspaceSession;
use App\Models\PilotLocation;
use App\Models\CrossCountrySession;
use App\Models\FlyingLocation;
use Illuminate\Http\Request;

class PilotLocationController extends Controller
{
    /**
     * =========================================================================
     * UPDATE PILOT GPS
     * =========================================================================
     *
     * Called by the pilot's phone whenever a new GPS position is available.
     */
    public function update(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Find active Airspace session
        |--------------------------------------------------------------------------
        */

        $session = AirspaceSession::where(
                'pilot_id',
                auth()->id()
            )
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->latest('checked_in_at')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Cross Country session
        |--------------------------------------------------------------------------
        |
        | Your current implementation does not yet have a Cross Country
        | session lookup, so this remains null.
        |
        */

        $crossCountrySession = null;

        /*
        |--------------------------------------------------------------------------
        | No active flight
        |--------------------------------------------------------------------------
        */

        if (!$session && !$crossCountrySession) {
            return response()->json([
                'success' => false,
                'message' => 'No active flight session.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Check whether pilot is outside authorized flying zone
        |--------------------------------------------------------------------------
        */

        $isOutsideZone = false;

        if ($session) {

            $allowedLocation = $this->findCurrentFlyingLocation(
                (float) $request->latitude,
                (float) $request->longitude
            );

            if (
                !$allowedLocation ||
                $allowedLocation->id !== $session->flying_location_id
            ) {
                $isOutsideZone = true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Save GPS position
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | We create a NEW GPS record.
        |
        | This allows us to keep the complete flight path.
        |
        */

        $location = PilotLocation::create([
            'pilot_id' => auth()->id(),

            'airspace_session_id' => $session?->id,

            'cross_country_session_id' => $crossCountrySession?->id,

            'latitude' => (float) $request->latitude,

            'longitude' => (float) $request->longitude,

            'accuracy' => $request->accuracy,

            'is_outside_zone' => $isOutsideZone,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Cross Country current location
        |--------------------------------------------------------------------------
        */

        if ($crossCountrySession) {

            $currentLocation = $this->findCurrentFlyingLocation(
                (float) $request->latitude,
                (float) $request->longitude
            );

            if (
                $currentLocation &&
                $crossCountrySession->current_location_id !==
                    $currentLocation->id
            ) {

                $crossCountrySession->update([
                    'current_location_id' => $currentLocation->id,
                ]);

                $crossCountrySession->refresh();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Return saved GPS
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'location' => [
                'id' => $location->id,

                'pilot_id' => $location->pilot_id,

                'airspace_session_id' =>
                    $location->airspace_session_id,

                'cross_country_session_id' =>
                    $location->cross_country_session_id,

                'latitude' => (float) $location->latitude,

                'longitude' => (float) $location->longitude,

                'accuracy' => $location->accuracy,

                'is_outside_zone' =>
                    (bool) $location->is_outside_zone,

                'created_at' => $location->created_at,
            ],

            'outside_zone' => $isOutsideZone,

            'message' => $isOutsideZone
                ? 'You are outside the authorized flying zone.'
                : null,
        ]);
    }


    /**
     * =========================================================================
     * LIVE TRACKING - ALL ACTIVE PILOTS
     * =========================================================================
     */
    public function liveAll()
    {
        /*
        |--------------------------------------------------------------------------
        | Expire old sessions
        |--------------------------------------------------------------------------
        */

        AirspaceSession::where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Get active sessions
        |--------------------------------------------------------------------------
        */

        $sessions = AirspaceSession::with([
                'pilot.pilotProfile',
                'location',
            ])
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->latest('checked_in_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Build response
        |--------------------------------------------------------------------------
        */

        $result = $sessions->map(function ($session) {

            /*
            |--------------------------------------------------------------------------
            | Get latest GPS specifically for THIS session
            |--------------------------------------------------------------------------
            */

            $latestGps = PilotLocation::where(
                    'airspace_session_id',
                    $session->id
                )
                ->latest('created_at')
                ->first();

            return [
                'id' => $session->id,

                'type' => 'airspace',

                'pilot' => $session->pilot,

                'location' => $session->location,

                'gps' => $latestGps
                    ? [
                        'id' => $latestGps->id,

                        'pilot_id' =>
                            $latestGps->pilot_id,

                        'airspace_session_id' =>
                            $latestGps->airspace_session_id,

                        'latitude' =>
                            (float) $latestGps->latitude,

                        'longitude' =>
                            (float) $latestGps->longitude,

                        'accuracy' =>
                            $latestGps->accuracy,

                        'is_outside_zone' =>
                            (bool) $latestGps->is_outside_zone,

                        'created_at' =>
                            $latestGps->created_at,
                    ]
                    : null,

                'outside_zone' =>
                    (bool) (
                        $latestGps?->is_outside_zone ?? false
                    ),

                'warning' =>
                    $latestGps?->is_outside_zone
                        ? 'Pilot is outside the authorized flying zone.'
                        : null,

                'started_at' =>
                    $session->checked_in_at,

                /*
                |--------------------------------------------------------------------------
                | Useful for the frontend
                |--------------------------------------------------------------------------
                */

                'last_gps_update' =>
                    $latestGps?->created_at,
            ];
        });

        return response()->json(
            $result->values()
        );
    }


    /**
     * =========================================================================
     * LIVE TRACKING - ONE FLYING LOCATION
     * =========================================================================
     */
    public function live($locationId)
    {
        /*
        |--------------------------------------------------------------------------
        | Expire old sessions
        |--------------------------------------------------------------------------
        */

        AirspaceSession::where(
                'flying_location_id',
                $locationId
            )
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Get active sessions
        |--------------------------------------------------------------------------
        */

        $sessions = AirspaceSession::with([
                'pilot.pilotProfile',
                'location',
            ])
            ->where(
                'flying_location_id',
                $locationId
            )
            ->where('status', 'active')
            ->whereNull('checked_out_at')
            ->where('expires_at', '>', now())
            ->latest('checked_in_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Build response
        |--------------------------------------------------------------------------
        */

        $result = $sessions->map(function ($session) {

            /*
            |--------------------------------------------------------------------------
            | Get latest GPS for THIS session
            |--------------------------------------------------------------------------
            */

            $latestGps = PilotLocation::where(
                    'airspace_session_id',
                    $session->id
                )
                ->latest('created_at')
                ->first();

            return [
                'id' => $session->id,

                'type' => 'airspace',

                'pilot' => $session->pilot,

                'location' => $session->location,

                'gps' => $latestGps
                    ? [
                        'id' => $latestGps->id,

                        'pilot_id' =>
                            $latestGps->pilot_id,

                        'airspace_session_id' =>
                            $latestGps->airspace_session_id,

                        'latitude' =>
                            (float) $latestGps->latitude,

                        'longitude' =>
                            (float) $latestGps->longitude,

                        'accuracy' =>
                            $latestGps->accuracy,

                        'is_outside_zone' =>
                            (bool) $latestGps->is_outside_zone,

                        'created_at' =>
                            $latestGps->created_at,
                    ]
                    : null,

                'outside_zone' =>
                    (bool) (
                        $latestGps?->is_outside_zone ?? false
                    ),

                'warning' =>
                    $latestGps?->is_outside_zone
                        ? 'Pilot is outside the authorized flying zone.'
                        : null,

                'started_at' =>
                    $session->checked_in_at,

                'last_gps_update' =>
                    $latestGps?->created_at,
            ];
        });

        return response()->json(
            $result->values()
        );
    }


    /**
     * =========================================================================
     * FIND CURRENT FLYING LOCATION
     * =========================================================================
     */
    private function findCurrentFlyingLocation(
        float $latitude,
        float $longitude
    ): ?FlyingLocation {

        foreach (
            FlyingLocation::whereNotNull('kml_polygon')->get()
            as $location
        ) {

            $polygons = $location->kml_polygon;

            if (!is_array($polygons)) {
                continue;
            }

            foreach ($polygons as $polygon) {

                if (
                    $this->pointInPolygon(
                        $latitude,
                        $longitude,
                        $polygon
                    )
                ) {
                    return $location;
                }
            }
        }

        return null;
    }


    /**
     * =========================================================================
     * DISTANCE BETWEEN TWO GPS POINTS
     * =========================================================================
     */
    private function distance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {

        $earthRadius = 6371;

        $dLat = deg2rad(
            $lat2 - $lat1
        );

        $dLon = deg2rad(
            $lon2 - $lon1
        );

        $a =
            sin($dLat / 2) *
            sin($dLat / 2)
            +
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


    /**
     * =========================================================================
     * CHECK IF POINT IS INSIDE POLYGON
     * =========================================================================
     */
    private function pointInPolygon(
        float $latitude,
        float $longitude,
        array $polygon
    ): bool {

        $inside = false;

        $vertices = count($polygon);

        if ($vertices < 3) {
            return false;
        }

        for (
            $i = 0,
            $j = $vertices - 1;
            $i < $vertices;
            $j = $i++
        ) {

            $xi = $polygon[$i]['lng'];
            $yi = $polygon[$i]['lat'];

            $xj = $polygon[$j]['lng'];
            $yj = $polygon[$j]['lat'];

            $intersect =
                (
                    ($yi > $latitude)
                    !==
                    ($yj > $latitude)
                )
                &&
                (
                    $longitude
                    <
                    ($xj - $xi)
                    *
                    ($latitude - $yi)
                    /
                    ($yj - $yi)
                    +
                    $xi
                );

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}