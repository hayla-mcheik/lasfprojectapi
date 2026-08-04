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
        'role',
        'is_approved',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Role Helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }


    public function isArmy(): bool
    {
        return $this->role === 'army';
    }


    public function isWatcher(): bool
    {
        return $this->role === 'watcher';
    }
     
    public function isBeirutAirport()
{
    return $this->role === 'beirut_airport';
}

    /*
    |--------------------------------------------------------------------------
    | Dashboard Access
    |--------------------------------------------------------------------------
    |
    | Admin
    | Army
    | Watcher
    |
    */

    public function canAccessDashboard(): bool
    {
        return $this->isAdmin()
            || $this->isArmy()
            || $this->isWatcher() 
            || $this->role === 'beirut_airport';
    }


    /*
    |--------------------------------------------------------------------------
    | Live Tracking Access
    |--------------------------------------------------------------------------
    */

  public function canViewLiveTracking(): bool
{
    return $this->isAdmin()
        || $this->isArmy()
        || $this->isWatcher();
}


    /*
    |--------------------------------------------------------------------------
    | Flying Location Management
    |--------------------------------------------------------------------------
    |
    | Admin + Army
    |
    */

    public function canManageLocations(): bool
    {
        return $this->isAdmin()
            || $this->isArmy();
    }


    /*
    |--------------------------------------------------------------------------
    | Pilots List Access
    |--------------------------------------------------------------------------
    |
    | Admin = manage
    | Army = view only
    |
    */

    public function canViewPilots(): bool
    {
        return $this->isAdmin()
            || $this->isArmy();
    }


    public function canManagePilots(): bool
    {
        return $this->isAdmin();
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function pilotProfile()
    {
        return $this->hasOne(PilotProfile::class);
    }


    public function newsCreated()
    {
        return $this->hasMany(
            News::class,
            'created_by'
        );
    }


    public function airspaceSessions()
    {
        return $this->hasMany(
            AirspaceSession::class,
            'pilot_id'
        );
    }


    public function gpsLocations()
    {
        return $this->hasMany(
            PilotLocation::class,
            'pilot_id'
        );
    }
        public function crossCountryRequests()
    {
        return $this->hasMany(
            CrossCountryRequest::class,
            'pilot_id'
        );
    }
    public function crossCountrySessions()
{
    return $this->hasMany(
        CrossCountrySession::class,
        'pilot_id'
    );
}
}