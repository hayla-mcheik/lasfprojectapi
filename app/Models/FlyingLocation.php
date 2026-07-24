<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FlyingLocation extends Model
{
    protected $fillable = [
        'type',
        'name',
        'slug',
        'takeoff_kato',
        'takeoff_nazim',
        'landing_kato',
        'landing_nazim',
        'boundaries_kato',
        'boundaries_nazim',
        'max_altitude',
        'map_image',
        'is_enabled',
    ];

    protected $casts = [
        'boundaries_kato' => 'array',
        'boundaries_nazim' => 'array',
        'is_enabled' => 'boolean',
    ];

    protected $appends = [
        'status_label',
        'latitude',
        'longitude',
    ];

    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    |
    | Uses the loaded clearanceStatuses relationship.
    | The controller is responsible for loading only the requested date.
    |
    */

public function getStatusLabelAttribute(): string
{
    if ($this->relationLoaded('clearanceStatuses')) {

        $status = $this->clearanceStatuses
            ->sortByDesc('permission_date')
            ->first();

        return $status?->status ?? 'red';
    }

    return 'red';
}

    /*
    |--------------------------------------------------------------------------
    | Coordinates
    |--------------------------------------------------------------------------
    */

    public function getLatitudeAttribute(): float
    {
        $bounds = $this->boundaries_kato;

        if (
            is_array($bounds)
            && isset($bounds[0]['lat'])
        ) {
            return (float) $bounds[0]['lat'];
        }

        return 33.5 + ($this->id * 0.05);
    }

    public function getLongitudeAttribute(): float
    {
        $bounds = $this->boundaries_kato;

        if (
            is_array($bounds)
            && isset($bounds[0]['lng'])
        ) {
            return (float) $bounds[0]['lng'];
        }

        return 35.2 + ($this->id * 0.05);
    }

    /*
    |--------------------------------------------------------------------------
    | Sports
    |--------------------------------------------------------------------------
    */

    public function sports()
    {
        return $this->belongsToMany(
            Sport::class,
            'flying_location_sport'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Clearance Statuses
    |--------------------------------------------------------------------------
    */

    public function clearanceStatuses(): HasMany
    {
        return $this->hasMany(
            ClearanceStatus::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    */

    public function airspaceSessions(): HasMany
    {
        return $this->hasMany(
            AirspaceSession::class
        );
    }

    public function activeSessions(): HasMany
    {
        return $this->hasMany(
            AirspaceSession::class
        )
        ->where('status', 'active')
        ->whereNull('checked_out_at')
        ->where('expires_at', '>', now());
    }

    /*
    |--------------------------------------------------------------------------
    | QR Code
    |--------------------------------------------------------------------------
    */

    public function qrCode(): HasOne
    {
        return $this->hasOne(
            QRCode::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | News
    |--------------------------------------------------------------------------
    */

    public function news()
    {
        return $this->belongsToMany(
            News::class,
            'flying_location_news'
        );
    }
        public function crossCountryLocations()
    {
        return $this->hasMany(
            CrossCountryRequestLocation::class,
            'flying_location_id'
        );
    }
    public function crossCountrySessions()
{
    return $this->hasMany(
        CrossCountrySession::class,
        'current_location_id'
    );
}
}