<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotSafetyMessage extends Model
{
        protected $fillable = [
        'title',
        'message',
        'active',
    ];
}
