<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherEvent extends Model
{
    protected $fillable = [
        'weather_bulletin_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(
            WeatherBulletin::class,
            'weather_bulletin_id'
        );
    }
}