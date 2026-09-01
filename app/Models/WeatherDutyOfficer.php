<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherDutyOfficer extends Model
{
    protected $fillable = [
        'weather_bulletin_id',
        'name_ar',
        'name_en',
        'position_ar',
        'position_en',
        'position_fr',
        'primary',
    ];

    protected $casts = [
        'primary' => 'boolean',
    ];

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(
            WeatherBulletin::class,
            'weather_bulletin_id'
        );
    }
}