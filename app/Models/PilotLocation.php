<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotLocation extends Model
{
      protected $fillable = [
        'pilot_id',
        'airspace_session_id',
        'latitude',
        'longitude',
        'accuracy',
        'is_outside_zone',
    ];

public function pilot()
{
    return $this->belongsTo(User::class, 'pilot_id');
}
    
public function session()
{
    return $this->belongsTo(AirspaceSession::class, 'airspace_session_id');
}
}
