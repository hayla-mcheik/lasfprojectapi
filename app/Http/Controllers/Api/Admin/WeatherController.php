<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WeatherForecast;
use Illuminate\Support\Facades\DB;

class WeatherController extends Controller
{
    /**
     * Get the single latest forecast for editing.
     */
    public function index()
    {
        $forecast = WeatherForecast::with(['regionalTemperatures', 'precipitationStats'])
            ->orderBy('forecast_date', 'desc')
            ->first();

        return response()->json([
            'forecast' => $forecast
        ]);
    }

    /**
     * Update the existing forecast.
     */
    public function update(Request $request, $id)
    {
        $forecast = WeatherForecast::findOrFail($id);

        return DB::transaction(function () use ($request, $forecast) {
            // Update main fields
            $forecast->update($request->all());

            // Sync Temperatures
            if ($request->has('temperatures')) {
                $forecast->regionalTemperatures()->delete();
                $forecast->regionalTemperatures()->createMany($request->temperatures);
            }

            // Sync Precipitation
            if ($request->has('precipitation')) {
                $forecast->precipitationStats()->delete();
                foreach ($request->precipitation as $stat) {
                    $forecast->precipitationStats()->create($stat);
                }
            }

            return response()->json(['success' => true, 'data' => $forecast->load(['regionalTemperatures', 'precipitationStats'])]);
        });
    }
}