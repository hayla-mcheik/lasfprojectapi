<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherRegionAggregate extends Model
{
    protected $fillable = [
        'weather_bulletin_id',
        'region_ar',
        'region_en',
        'day',
        'tmin',
        'tmax',
    ];

    protected $casts = [
        'day' => 'date',
        'tmin' => 'decimal:2',
        'tmax' => 'decimal:2',
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(
            WeatherBulletin::class,
            'weather_bulletin_id'
        );
    }
}