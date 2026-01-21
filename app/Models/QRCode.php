<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QRCode extends Model
{
    protected $fillable = [
        'flying_location_id',
        'token',
    ];

    public function location()
    {
        return $this->belongsTo(FlyingLocation::class, 'flying_location_id');
    }
}
