<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackReport extends Model
{
    protected $fillable = [

        'type',

        'subject',

        'message',

        'flying_location_id',

        'incident_date',

        'attachment',

        'status',

        'admin_notes',

    ];

    public function location()
    {
        return $this->belongsTo(

            FlyingLocation::class,

            'flying_location_id'

        );
    }
}