<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeatherBulletin;

class WeatherController extends Controller
{
    /**
     * Get the latest weather bulletin.
     */
    public function index()
    {
        $bulletin = WeatherBulletin::with([
            'forecastDays',
            'temperatures',
            'regionAggregates',
            'dutyOfficers',
            'events',
        ])
        ->orderBy('date', 'desc')
        ->orderBy('api_id', 'desc')
        ->first();

        if (!$bulletin) {
            return response()->json([
                'success' => false,
                'message' => 'No weather bulletin available.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'bulletin' => $bulletin,
            ],
        ]);
    }
}