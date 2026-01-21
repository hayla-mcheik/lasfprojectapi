<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AirspaceSession extends Model
{
    protected $fillable = [
        'flying_location_id',
        'pilot_id',
        'checked_in_at',
        'checked_out_at',
        'expires_at',
        'status',
    ];

    public function location()
    {
        return $this->belongsTo(FlyingLocation::class, 'flying_location_id');
    }

    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }
}
