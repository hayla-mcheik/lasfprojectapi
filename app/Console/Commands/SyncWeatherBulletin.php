<?php

namespace App\Console\Commands;

use App\Models\WeatherBulletin;
use App\Models\WeatherForecastDay;
use App\Models\WeatherTemperature;
use App\Models\WeatherRegionAggregate;
use App\Models\WeatherDutyOfficer;
use App\Models\WeatherEvent;
use App\Services\WeatherApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncWeatherBulletin extends Command
{
    protected $signature = 'weather:sync';

    protected $description =
        'Fetch and synchronize the latest weather bulletin from LCAA API';

    public function handle(WeatherApiService $weatherApi)
    {
        try {

            $data = $weatherApi->getLatestBulletin();

            if (!isset($data['bulletin'])) {
                throw new \Exception(
                    'Invalid API response: bulletin is missing.'
                );
            }

            $apiBulletin = $data['bulletin'];

            $this->info('Weather API request successful.');

            $result = DB::transaction(function () use (
                $data,
                $apiBulletin
            ) {

                /*
                |--------------------------------------------------------------------------
                | BULLETIN
                |--------------------------------------------------------------------------
                */

                $bulletin = WeatherBulletin::updateOrCreate(
                    [
                        'api_id' => $apiBulletin['id'],
                    ],
                    [
                        'date' => $apiBulletin['date'] ?? null,

                        'api_created_at' =>
                            $apiBulletin['created_at'] ?? null,

                        'api_updated_at' =>
                            $apiBulletin['updated_at'] ?? null,

                        'is_translated' =>
                            $apiBulletin['is_translated'] ?? false,

                        /*
                        | State
                        */

                        'state_ar' =>
                            data_get($apiBulletin, 'state.ar'),

                        'state_en' =>
                            data_get($apiBulletin, 'state.en'),

                        'state_fr' =>
                            data_get($apiBulletin, 'state.fr'),

                        /*
                        | Humidity
                        */

                        'humidity_ar' =>
                            data_get($apiBulletin, 'humidity.ar'),

                        'humidity_en' =>
                            data_get($apiBulletin, 'humidity.en'),

                        'humidity_fr' =>
                            data_get($apiBulletin, 'humidity.fr'),

                        /*
                        | Wind
                        */

                        'wind_ar' =>
                            data_get($apiBulletin, 'wind.ar'),

                        'wind_en' =>
                            data_get($apiBulletin, 'wind.en'),

                        'wind_fr' =>
                            data_get($apiBulletin, 'wind.fr'),

                        /*
                        | Sea
                        */

                        'sea_ar' =>
                            data_get($apiBulletin, 'sea.ar'),

                        'sea_en' =>
                            data_get($apiBulletin, 'sea.en'),

                        'sea_fr' =>
                            data_get($apiBulletin, 'sea.fr'),

                        /*
                        | Visibility
                        */

                        'visibility_ar' =>
                            data_get($apiBulletin, 'visibility.ar'),

                        'visibility_en' =>
                            data_get($apiBulletin, 'visibility.en'),

                        'visibility_fr' =>
                            data_get($apiBulletin, 'visibility.fr'),

                        /*
                        | Measurements
                        */

                        'water_temp_c' =>
                            $apiBulletin['water_temp_c'] ?? null,

                        'pressure_hpa' =>
                            $apiBulletin['pressure_hpa'] ?? null,

                        'sunrise' =>
                            $apiBulletin['sunrise'] ?? null,

                        'sunset' =>
                            $apiBulletin['sunset'] ?? null,
                    ]
                );


                /*
                |--------------------------------------------------------------------------
                | Remove old child data for this bulletin
                |--------------------------------------------------------------------------
                |
                | This makes the sync safe if the API updates the content
                | of the same bulletin.
                |
                */

                $bulletin->forecastDays()->delete();
                $bulletin->temperatures()->delete();
                $bulletin->regionAggregates()->delete();
                $bulletin->dutyOfficers()->delete();
                $bulletin->events()->delete();


                /*
                |--------------------------------------------------------------------------
                | FORECAST DAYS
                |--------------------------------------------------------------------------
                */

                foreach ($data['forecast_days'] ?? [] as $forecastDay) {

                    WeatherForecastDay::create([
                        'weather_bulletin_id' => $bulletin->id,

                        'day' =>
                            $forecastDay['day'] ?? null,

                        'state_ar' =>
                            data_get($forecastDay, 'state.ar'),

                        'state_en' =>
                            data_get($forecastDay, 'state.en'),

                        'state_fr' =>
                            data_get($forecastDay, 'state.fr'),
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | TEMPERATURES
                |--------------------------------------------------------------------------
                */

                foreach ($data['temperatures'] ?? [] as $temperature) {

                    $city = $temperature['city'] ?? [];

                    foreach ($temperature['days'] ?? [] as $day) {

                        WeatherTemperature::create([

                            'weather_bulletin_id' =>
                                $bulletin->id,

                            'city_id' =>
                                $city['id'] ?? null,

                            'city_name' =>
                                $city['name'] ?? null,

                            'city_name_ar' =>
                                $city['name_ar'] ?? null,

                            'region_ar' =>
                                data_get(
                                    $city,
                                    'region.ar'
                                ),

                            'region_en' =>
                                data_get(
                                    $city,
                                    'region.en'
                                ),

                            'latitude' =>
                                $city['latitude'] ?? null,

                            'longitude' =>
                                $city['longitude'] ?? null,

                            'city_order' =>
                                $city['order'] ?? null,

                            'exclude_from_temperature_charts' =>
                                $city['exclude_from_temperature_charts']
                                ?? false,

                            'exclude_from_precipitation_charts' =>
                                $city['exclude_from_precipitation_charts']
                                ?? false,

                            'day' =>
                                $day['day'] ?? null,

                            'tmin' =>
                                $day['tmin'] ?? null,

                            'tmax' =>
                                $day['tmax'] ?? null,

                            'rr_24' =>
                                $day['rr_24'] ?? null,

                            'rr_cumul' =>
                                $day['rr_cumul'] ?? null,

                            'rr_avg_today' =>
                                $day['rr_avg_today'] ?? null,

                            'rr_avg' =>
                                $day['rr_avg'] ?? null,

                            'rr_last_year' =>
                                $day['rr_last_year'] ?? null,
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | REGION AGGREGATES
                |--------------------------------------------------------------------------
                */

                foreach (
                    $data['region_aggregates'] ?? []
                    as $regionAggregate
                ) {

                    $region = $regionAggregate['region'] ?? [];

                    foreach (
                        $regionAggregate['days'] ?? []
                        as $day
                    ) {

                        WeatherRegionAggregate::create([

                            'weather_bulletin_id' =>
                                $bulletin->id,

                            'region_ar' =>
                                $region['ar'] ?? null,

                            'region_en' =>
                                $region['en'] ?? null,

                            'day' =>
                                $day['day'] ?? null,

                            'tmin' =>
                                $day['tmin'] ?? null,

                            'tmax' =>
                                $day['tmax'] ?? null,
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | DUTY OFFICERS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $data['duty_officers'] ?? []
                    as $officer
                ) {

                    WeatherDutyOfficer::create([

                        'weather_bulletin_id' =>
                            $bulletin->id,

                        'name_ar' =>
                            data_get($officer, 'name.ar'),

                        'name_en' =>
                            data_get($officer, 'name.en'),

                        'position_ar' =>
                            data_get($officer, 'position.ar'),

                        'position_en' =>
                            data_get($officer, 'position.en'),

                        'position_fr' =>
                            data_get($officer, 'position.fr'),

                        'primary' =>
                            $officer['primary'] ?? false,
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | EVENTS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $data['events'] ?? []
                    as $event
                ) {

                    WeatherEvent::create([

                        'weather_bulletin_id' =>
                            $bulletin->id,

                        'data' =>
                            $event,
                    ]);
                }


                return [
                    'bulletin' =>
                        $bulletin,

                    'forecast_days' =>
                        $bulletin->forecastDays()->count(),

                    'temperatures' =>
                        $bulletin->temperatures()->count(),

                    'region_aggregates' =>
                        $bulletin->regionAggregates()->count(),

                    'duty_officers' =>
                        $bulletin->dutyOfficers()->count(),

                    'events' =>
                        $bulletin->events()->count(),
                ];
            });


            /*
            |--------------------------------------------------------------------------
            | OUTPUT
            |--------------------------------------------------------------------------
            */

            $this->newLine();

            $this->info(
                'Bulletin #' .
                $result['bulletin']->api_id .
                ' synchronized successfully.'
            );

            $this->line(
                'Forecast days: ' .
                $result['forecast_days']
            );

            $this->line(
                'Temperature records: ' .
                $result['temperatures']
            );

            $this->line(
                'Region aggregates: ' .
                $result['region_aggregates']
            );

            $this->line(
                'Duty officers: ' .
                $result['duty_officers']
            );

            $this->line(
                'Events: ' .
                $result['events']
            );

            $this->newLine();

            $this->info(
                'Weather synchronization completed successfully.'
            );

            return self::SUCCESS;

        } catch (\Throwable $e) {

            $this->error(
                'Weather sync failed: ' .
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}