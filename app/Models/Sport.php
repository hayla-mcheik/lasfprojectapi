<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
protected $fillable = ['name','image','description'];


    public function flyingLocations()
    {
        return $this->belongsToMany(FlyingLocation::class, 'flying_location_sport');
    }
}
