<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WeatherForecast;
use App\Models\PrecipitationStat;
use Illuminate\Support\Facades\DB;

class WeatherController extends Controller
{
    /**
     * Display a listing of weather forecasts and precipitation stats.
     */
    public function index()
    {
        return response()->json([
            // FIX: Added 'precipitationStats' to eager load the relationship data
            'forecasts' => WeatherForecast::with(['regionalTemperatures', 'precipitationStats'])
                ->orderBy('forecast_date', 'desc')
                ->get()
        ]);
    }

    /**
     * Store a newly created forecast in storage.
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $forecast = WeatherForecast::create($request->all());

            if ($request->has('temperatures')) {
                $forecast->regionalTemperatures()->createMany($request->temperatures);
            }

            // LINKED: Save precipitation stats with the forecast ID
            if ($request->has('precipitation')) {
                foreach ($request->precipitation as $stat) {
                    $forecast->precipitationStats()->create($stat);
                }
            }

            return response()->json(['success' => true, 'data' => $forecast->load('precipitationStats')]);
        });
    }

    public function update(Request $request, $id)
    {
        $forecast = WeatherForecast::findOrFail($id);

        return DB::transaction(function () use ($request, $forecast) {
            $forecast->update($request->all());

            // Refresh Temperatures
            $forecast->regionalTemperatures()->delete();
            $forecast->regionalTemperatures()->createMany($request->temperatures);

            // REFRESH: Replace old stats with new ones for this report
            if ($request->has('precipitation')) {
                $forecast->precipitationStats()->delete();
                foreach ($request->precipitation as $stat) {
                    $forecast->precipitationStats()->create($stat);
                }
            }

            return response()->json(['success' => true]);
        });
    }

    public function destroy($id)
    {
        $forecast = WeatherForecast::findOrFail($id);
        $forecast->delete();
        return response()->json(['success' => true]);
    }
}