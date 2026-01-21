<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    protected $fillable = ['title', 'content', 'image', 'mission', 'vision'];
protected $casts = ['mission' => 'array', 'vision' => 'array'];
}
