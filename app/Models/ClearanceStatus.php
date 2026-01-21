<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClearanceStatus extends Model
{
    protected $fillable = [
        'flying_location_id',
        'status',
        'reason',
        'updated_by',
    ];

    public function location()
    {
        return $this->belongsTo(FlyingLocation::class, 'flying_location_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
