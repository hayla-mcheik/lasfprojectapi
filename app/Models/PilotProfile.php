<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotProfile extends Model
{
    protected $fillable = [
        'user_id',
        'license_number',
        'license_type',
        'issued_by',
        'expiry_date',
        'club_name',
        'image',
        'facebook_url',
        'instagram_url',
        'designation'

    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function airspaceSessions()
    {
        return $this->hasMany(AirspaceSession::class, 'pilot_id');
    }
}
