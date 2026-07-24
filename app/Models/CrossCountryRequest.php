<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrossCountryRequest extends Model
{
    use HasFactory;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Pilot
        |--------------------------------------------------------------------------
        */

        'pilot_id',

        /*
        |--------------------------------------------------------------------------
        | Flight Information
        |--------------------------------------------------------------------------
        */

        'flight_date',

        'takeoff_time',

        'estimated_landing_time',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',

        /*
        |--------------------------------------------------------------------------
        | Army
        |--------------------------------------------------------------------------
        */

        'approved_by',

        'approved_at',

        'rejection_reason',

        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        'notes',

    ];

    protected $casts = [

        'flight_date' => 'date',

        'approved_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Pilot
     */
    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    /**
     * Admin that approved the request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Route Locations
     */
    public function locations()
    {
        return $this->hasMany(
            CrossCountryRequestLocation::class
        )->orderBy('route_order');
    }
public function session()
{
    return $this->hasOne(
        CrossCountrySession::class,
        'cross_country_request_id'
    );
}
public function qrCode()
{
    return $this->hasOne(
        QRCode::class,
        'cross_country_request_id'
    );
}
public function activeSession()
{
    return $this->hasOne(CrossCountrySession::class)
        ->where('is_active', true);
}

}