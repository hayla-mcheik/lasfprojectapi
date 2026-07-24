<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClearanceStatus extends Model
{
    protected $fillable = [
        'flying_location_id',
        'permission_date',
        'status',
        'reason',
        'updated_by',
    ];

    protected $casts = [
        'permission_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function location(): BelongsTo
    {
        return $this->belongsTo(
            FlyingLocation::class,
            'flying_location_id'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function histories(): HasMany
    {
        return $this->hasMany(
            ClearanceStatusHistory::class,
            'clearance_status_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForDate($query, $date)
    {
        return $query->whereDate(
            'permission_date',
            $date
        );
    }

    public function scopeForLocation($query, $locationId)
    {
        return $query->where(
            'flying_location_id',
            $locationId
        );
    }
}