<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotLocation extends Model
{
    protected $fillable = [
        'pilot_id',
        'airspace_session_id',
        'cross_country_session_id',
        'latitude',
        'longitude',
        'accuracy',
        'is_outside_zone',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'is_outside_zone' => 'boolean',
    ];

    /**
     * Pilot who sent this GPS position.
     */
    public function pilot()
    {
        return $this->belongsTo(
            User::class,
            'pilot_id'
        );
    }

    /**
     * Airspace session.
     */
    public function session()
    {
        return $this->belongsTo(
            AirspaceSession::class,
            'airspace_session_id'
        );
    }

    /**
     * Cross-country session.
     */
    public function crossCountrySession()
    {
        return $this->belongsTo(
            CrossCountrySession::class,
            'cross_country_session_id'
        );
    }
}