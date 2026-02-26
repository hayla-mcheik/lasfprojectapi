<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeatherForecast;

class WeatherController extends Controller
{
    public function index()
    {
        // FIX: Fetch the latest report with BOTH temperatures AND linked rainfall stats
        $report = WeatherForecast::with(['regionalTemperatures', 'precipitationStats'])
            ->orderBy('forecast_date', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report
            ]
        ]);
    }
}