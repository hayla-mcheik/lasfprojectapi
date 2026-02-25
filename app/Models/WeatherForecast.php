<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeatherForecast extends Model
{
    protected $fillable = [
        'forecast_date', 'day_name_ar', 'general_situation_ar', 
        'daily_description_ar', 'daily_details', 'sea_state_ar', 
        'water_temp_ar', 'pressure_hpa', 'sunrise', 'sunset',
        'surface_winds_ar', 'visibility_ar', 'humidity_range'
    ];

    protected $casts = [
        'daily_details' => 'array',
        'forecast_date' => 'date',
    ];

    public function regionalTemperatures(): HasMany
    {
        return $this->hasMany(RegionalTemperature::class);
    }

    // ADD THIS: Relationship for Precipitation
    public function precipitationStats(): HasMany
    {
        return $this->hasMany(PrecipitationStat::class);
    }
}