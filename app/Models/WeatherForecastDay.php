<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherForecastDay extends Model
{
    protected $fillable = [
        'weather_bulletin_id',
        'day',
        'state_ar',
        'state_en',
        'state_fr',
    ];

    protected $casts = [
        'day' => 'date',
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(
            WeatherBulletin::class,
            'weather_bulletin_id'
        );
    }
}