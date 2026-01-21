<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function pilotProfile()
    {
        return $this->hasOne(PilotProfile::class);
    }

    public function newsCreated()
    {
        return $this->hasMany(News::class, 'created_by');
    }

    public function airspaceSessions()
    {
        return $this->hasMany(AirspaceSession::class, 'pilot_id');
    }
}
