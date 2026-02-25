<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecipitationStat extends Model
{
    protected $fillable = [
        'weather_forecast_id',
        'station_name_ar', 
        'last_24_hours', 
        'accumulated_total', 
        'previous_year_total', 
        'yearly_average'
    ];
}
