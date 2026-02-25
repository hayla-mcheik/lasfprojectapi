<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeatherForecast;
use App\Models\PrecipitationStat;

class WeatherController extends Controller
{
    /**
     * Get latest data for the homepage dashboard.
     */
    public function index()
    {
        // Fetch the single latest report with its temperatures
        $report = WeatherForecast::with('regionalTemperatures')
            ->orderBy('forecast_date', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'precipitation' => PrecipitationStat::limit(3)->get()
            ]
        ]);
    }
}