<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrossCountryQRCode extends Model
{
    protected $fillable = [

        'cross_country_request_id',

        'token',

    ];

    /*
    |--------------------------------------------------------------------------
    | Cross Country Request
    |--------------------------------------------------------------------------
    */

    public function request()
    {
        return $this->belongsTo(
            CrossCountryRequest::class,
            'cross_country_request_id'
        );
    }
}