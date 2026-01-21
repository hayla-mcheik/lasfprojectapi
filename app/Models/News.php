<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function categories()
    {
        return $this->belongsToMany(NewsCategory::class, 'news_news_category');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function affectedLocations()
    {
        return $this->belongsToMany(FlyingLocation::class,'flying_location_news');
    }
}
