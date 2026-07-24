<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QRCode extends Model
{
    protected $fillable = [

        'token',

        'type',

        'flying_location_id',

        'cross_country_request_id',

    ];

    /*
    |--------------------------------------------------------------------------
    | Airspace QR
    |--------------------------------------------------------------------------
    */

    public function location()
    {
        return $this->belongsTo(
            FlyingLocation::class,
            'flying_location_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cross Country QR
    |--------------------------------------------------------------------------
    */

    public function crossCountryRequest()
    {
        return $this->belongsTo(
            CrossCountryRequest::class,
            'cross_country_request_id'
        );
    }
}