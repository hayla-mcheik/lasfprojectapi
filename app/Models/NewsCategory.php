<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsCategory extends Model
{
    protected $fillable = ['name'];

    public function news()
    {
        return $this->belongsToMany(News::class, 'news_news_category');
    }
}
