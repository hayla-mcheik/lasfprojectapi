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
        'accuracy'
    ];

    public function pilot()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(AirspaceSession::class);
    }
}
