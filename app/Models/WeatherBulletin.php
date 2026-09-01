<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeatherBulletin extends Model
{
    protected $fillable = [
        'api_id',
        'date',
        'api_created_at',
        'api_updated_at',
        'is_translated',

        'state_ar',
        'state_en',
        'state_fr',

        'humidity_ar',
        'humidity_en',
        'humidity_fr',

        'wind_ar',
        'wind_en',
        'wind_fr',

        'sea_ar',
        'sea_en',
        'sea_fr',

        'visibility_ar',
        'visibility_en',
        'visibility_fr',

        'water_temp_c',
        'pressure_hpa',

        'sunrise',
        'sunset',
    ];

    protected $casts = [
        'date' => 'date',
        'api_created_at' => 'datetime',
        'api_updated_at' => 'datetime',
        'is_translated' => 'boolean',
        'water_temp_c' => 'decimal:2',
        'pressure_hpa' => 'decimal:2',
        'sunrise' => 'datetime:H:i',
        'sunset' => 'datetime:H:i',
    ];

    public function forecastDays(): HasMany
    {
        return $this->hasMany(
            WeatherForecastDay::class,
            'weather_bulletin_id'
        );
    }

    public function temperatures(): HasMany
    {
        return $this->hasMany(
            WeatherTemperature::class,
            'weather_bulletin_id'
        );
    }

    public function regionAggregates(): HasMany
    {
        return $this->hasMany(
            WeatherRegionAggregate::class,
            'weather_bulletin_id'
        );
    }

    public function dutyOfficers(): HasMany
    {
        return $this->hasMany(
            WeatherDutyOfficer::class,
            'weather_bulletin_id'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            WeatherEvent::class,
            'weather_bulletin_id'
        );
    }
}