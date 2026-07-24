<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrossCountryRequestLocation extends Model
{
    use HasFactory;

    protected $fillable = [

        'cross_country_request_id',

        'flying_location_id',

        'route_order',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Cross Country Request
     */
    public function request()
    {
        return $this->belongsTo(
            CrossCountryRequest::class,
            'cross_country_request_id'
        );
    }

    /**
     * Flying Location
     */
    public function location()
    {
        return $this->belongsTo(
            FlyingLocation::class,
            'flying_location_id'
        );
    }
}