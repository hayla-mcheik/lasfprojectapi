<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceStatusHistory extends Model
{
    protected $fillable = [
        'clearance_status_id',
        'flying_location_id',
        'permission_date',

        'old_status',
        'old_reason',

        'new_status',
        'new_reason',

        'changed_by',
        'action',
    ];

    protected $casts = [
        'permission_date' => 'date',
    ];

    public function clearanceStatus(): BelongsTo
    {
        return $this->belongsTo(ClearanceStatus::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(
            FlyingLocation::class,
            'flying_location_id'
        );
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'changed_by'
        );
    }
}