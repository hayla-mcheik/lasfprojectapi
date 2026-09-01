<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeatherBulletin;
use App\Models\WeatherForecastDay;
use App\Models\WeatherTemperature;
use App\Models\WeatherRegionAggregate;
use App\Models\WeatherDutyOfficer;
use App\Models\WeatherEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeatherController extends Controller
{
    /**
     * Get latest weather bulletin.
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

        return response()->json([
            'bulletin' => $bulletin,
        ]);
    }


    /**
     * Update weather bulletin.
     */
    public function update(Request $request, $id)
    {
        $bulletin = WeatherBulletin::findOrFail($id);

        $validated = $request->validate([

            'date' => 'required|date',

            'is_translated' => 'nullable|boolean',

            'state_ar' => 'nullable|string',
            'state_en' => 'nullable|string',
            'state_fr' => 'nullable|string',

            'humidity_ar' => 'nullable|string',
            'humidity_en' => 'nullable|string',
            'humidity_fr' => 'nullable|string',

            'wind_ar' => 'nullable|string',
            'wind_en' => 'nullable|string',
            'wind_fr' => 'nullable|string',

            'sea_ar' => 'nullable|string',
            'sea_en' => 'nullable|string',
            'sea_fr' => 'nullable|string',

            'visibility_ar' => 'nullable|string',
            'visibility_en' => 'nullable|string',
            'visibility_fr' => 'nullable|string',

            'water_temp_c' => 'nullable|numeric',
            'pressure_hpa' => 'nullable|numeric',

            'sunrise' => 'nullable|date_format:H:i',
            'sunset' => 'nullable|date_format:H:i',

            'forecast_days' => 'nullable|array',

            'temperatures' => 'nullable|array',

            'region_aggregates' => 'nullable|array',

            'duty_officers' => 'nullable|array',

            'events' => 'nullable|array',
        ]);


        DB::transaction(function () use (
            $bulletin,
            $validated,
            $request
        ) {

            /*
            |--------------------------------------------------------------------------
            | BULLETIN
            |--------------------------------------------------------------------------
            */

            $bulletin->update([
                'date' => $validated['date'],

                'is_translated' =>
                    $validated['is_translated']
                    ?? $bulletin->is_translated,

                'state_ar' =>
                    $validated['state_ar']
                    ?? null,

                'state_en' =>
                    $validated['state_en']
                    ?? null,

                'state_fr' =>
                    $validated['state_fr']
                    ?? null,

                'humidity_ar' =>
                    $validated['humidity_ar']
                    ?? null,

                'humidity_en' =>
                    $validated['humidity_en']
                    ?? null,

                'humidity_fr' =>
                    $validated['humidity_fr']
                    ?? null,

                'wind_ar' =>
                    $validated['wind_ar']
                    ?? null,

                'wind_en' =>
                    $validated['wind_en']
                    ?? null,

                'wind_fr' =>
                    $validated['wind_fr']
                    ?? null,

                'sea_ar' =>
                    $validated['sea_ar']
                    ?? null,

                'sea_en' =>
                    $validated['sea_en']
                    ?? null,

                'sea_fr' =>
                    $validated['sea_fr']
                    ?? null,

                'visibility_ar' =>
                    $validated['visibility_ar']
                    ?? null,

                'visibility_en' =>
                    $validated['visibility_en']
                    ?? null,

                'visibility_fr' =>
                    $validated['visibility_fr']
                    ?? null,

                'water_temp_c' =>
                    $validated['water_temp_c']
                    ?? null,

                'pressure_hpa' =>
                    $validated['pressure_hpa']
                    ?? null,

                'sunrise' =>
                    $validated['sunrise']
                    ?? null,

                'sunset' =>
                    $validated['sunset']
                    ?? null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | FORECAST DAYS
            |--------------------------------------------------------------------------
            */

            if ($request->has('forecast_days')) {

                $bulletin->forecastDays()->delete();

                foreach (
                    $request->input('forecast_days', [])
                    as $day
                ) {

                    WeatherForecastDay::create([

                        'weather_bulletin_id' =>
                            $bulletin->id,

                        'day' =>
                            $day['day'] ?? null,

                        'state_ar' =>
                            $day['state_ar'] ?? null,

                        'state_en' =>
                            $day['state_en'] ?? null,

                        'state_fr' =>
                            $day['state_fr'] ?? null,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | TEMPERATURES
            |--------------------------------------------------------------------------
            */

            if ($request->has('temperatures')) {

                $bulletin->temperatures()->delete();

                foreach (
                    $request->input('temperatures', [])
                    as $temperature
                ) {

                    WeatherTemperature::create([

                        'weather_bulletin_id' =>
                            $bulletin->id,

                        'city_id' =>
                            $temperature['city_id'] ?? null,

                        'city_name' =>
                            $temperature['city_name'] ?? null,

                        'city_name_ar' =>
                            $temperature['city_name_ar'] ?? null,

                        'region_ar' =>
                            $temperature['region_ar'] ?? null,

                        'region_en' =>
                            $temperature['region_en'] ?? null,

                        'latitude' =>
                            $temperature['latitude'] ?? null,

                        'longitude' =>
                            $temperature['longitude'] ?? null,

                        'city_order' =>
                            $temperature['city_order'] ?? null,

                        'exclude_from_temperature_charts' =>
                            $temperature[
                                'exclude_from_temperature_charts'
                            ] ?? false,

                        'exclude_from_precipitation_charts' =>
                            $temperature[
                                'exclude_from_precipitation_charts'
                            ] ?? false,

                        'day' =>
                            $temperature['day'] ?? null,

                        'tmin' =>
                            $temperature['tmin'] ?? null,

                        'tmax' =>
                            $temperature['tmax'] ?? null,

                        'rr_24' =>
                            $temperature['rr_24'] ?? null,

                        'rr_cumul' =>
                            $temperature['rr_cumul'] ?? null,

                        'rr_avg_today' =>
                            $temperature['rr_avg_today'] ?? null,

                        'rr_avg' =>
                            $temperature['rr_avg'] ?? null,

                        'rr_last_year' =>
                            $temperature['rr_last_year'] ?? null,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | REGION AGGREGATES
            |--------------------------------------------------------------------------
            */

            if ($request->has('region_aggregates')) {

                $bulletin->regionAggregates()->delete();

                foreach (
                    $request->input(
                        'region_aggregates',
                        []
                    ) as $region
                ) {

                    WeatherRegionAggregate::create([

                        'weather_bulletin_id' =>
                            $bulletin->id,

                        'region_ar' =>
                            $region['region_ar'] ?? null,

                        'region_en' =>
                            $region['region_en'] ?? null,

                        'day' =>
                            $region['day'] ?? null,

                        'tmin' =>
                            $region['tmin'] ?? null,

                        'tmax' =>
                            $region['tmax'] ?? null,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | DUTY OFFICERS
            |--------------------------------------------------------------------------
            */

            if ($request->has('duty_officers')) {

                $bulletin->dutyOfficers()->delete();

                foreach (
                    $request->input(
                        'duty_officers',
                        []
                    ) as $officer
                ) {

                    WeatherDutyOfficer::create([

                        'weather_bulletin_id' =>
                            $bulletin->id,

                        'name_ar' =>
                            $officer['name_ar'] ?? null,

                        'name_en' =>
                            $officer['name_en'] ?? null,

                        'position_ar' =>
                            $officer['position_ar'] ?? null,

                        'position_en' =>
                            $officer['position_en'] ?? null,

                        'position_fr' =>
                            $officer['position_fr'] ?? null,

                        'primary' =>
                            $officer['primary'] ?? false,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | EVENTS
            |--------------------------------------------------------------------------
            */

            if ($request->has('events')) {

                $bulletin->events()->delete();

                foreach (
                    $request->input(
                        'events',
                        []
                    ) as $event
                ) {

                    WeatherEvent::create([

                        'weather_bulletin_id' =>
                            $bulletin->id,

                        'data' =>
                            $event,
                    ]);
                }
            }
        });


        /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'Weather bulletin updated successfully.',

            'bulletin' =>
                $bulletin->fresh()->load([
                    'forecastDays',
                    'temperatures',
                    'regionAggregates',
                    'dutyOfficers',
                    'events',
                ]),
        ]);
    }
}