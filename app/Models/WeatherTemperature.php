<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherTemperature extends Model
{
    protected $fillable = [
        'weather_bulletin_id',

        'city_id',
        'city_name',
        'city_name_ar',

        'region_ar',
        'region_en',

        'latitude',
        'longitude',

        'city_order',

        'exclude_from_temperature_charts',
        'exclude_from_precipitation_charts',

        'day',

        'tmin',
        'tmax',

        'rr_24',
        'rr_cumul',
        'rr_avg_today',
        'rr_avg',
        'rr_last_year',
    ];

    protected $casts = [
        'day' => 'date',

        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',

        'tmin' => 'decimal:2',
        'tmax' => 'decimal:2',

        'rr_24' => 'decimal:2',
        'rr_cumul' => 'decimal:2',
        'rr_avg_today' => 'decimal:2',
        'rr_avg' => 'decimal:2',
        'rr_last_year' => 'decimal:2',

        'exclude_from_temperature_charts' => 'boolean',
        'exclude_from_precipitation_charts' => 'boolean',
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(
            WeatherBulletin::class,
            'weather_bulletin_id'
        );
    }
}