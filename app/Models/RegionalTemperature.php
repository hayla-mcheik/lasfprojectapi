<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionalTemperature extends Model
{
    protected $fillable = [
        'weather_forecast_id', 
        'region_type_ar', 
        'city_name_ar', 
        'temp_range'
    ];

    /**
     * Get the forecast that owns the temperature data.
     */
    public function forecast(): BelongsTo
    {
        return $this->belongsTo(WeatherForecast::class, 'weather_forecast_id');
    }
}
