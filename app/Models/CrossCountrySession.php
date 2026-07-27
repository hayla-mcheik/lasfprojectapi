<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrossCountrySession extends Model
{
    use HasFactory;

    protected $fillable = [

        'cross_country_request_id',

        'pilot_id',

        'current_location_id',

        'started_at',

        'ended_at',

        'is_active',

    ];

    protected $casts = [

        'started_at' => 'datetime',

        'ended_at' => 'datetime',

        'is_active' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function request()
    {
        return $this->belongsTo(
            CrossCountryRequest::class,
            'cross_country_request_id'
        );
    }

    public function pilot()
    {
        return $this->belongsTo(
            User::class,
            'pilot_id'
        );
    }

    public function currentLocation()
    {
        return $this->belongsTo(
            FlyingLocation::class,
            'current_location_id'
        );
    }
    public function locations()
{
    return $this->hasMany(
        PilotLocation::class,
        'cross_country_session_id'
    );
}

}