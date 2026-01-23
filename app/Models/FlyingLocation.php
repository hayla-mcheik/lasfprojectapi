<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlyingLocation extends Model
{
protected $fillable = [
        'type', 'name', 'slug', 'takeoff_kato', 'takeoff_nazim', 
        'landing_kato', 'landing_nazim', 'boundaries_kato', 
        'boundaries_nazim', 'max_altitude', 'map_image', 'is_enabled'
    ];

    // هذا الجزء هو الحل للمشكلة
    protected $casts = [
        'boundaries_kato' => 'array',
        'boundaries_nazim' => 'array',
    ];
    // app/Models/FlyingLocation.php
protected $appends = ['status_label'];

public function getStatusLabelAttribute()
{
    $lastStatus = $this->clearanceStatuses()->latest()->first();
    return $lastStatus ? $lastStatus->status : 'green';
}

    public function sports()
    {
        return $this->belongsToMany(Sport::class, 'flying_location_sport');
    }
public function latestClearanceStatus()
{
    return $this->hasOne(ClearanceStatus::class)->latest();
}
    public function clearanceStatuses()
    {
        return $this->hasMany(ClearanceStatus::class);
    }

    public function airspaceSessions()
    {
        return $this->hasMany(AirspaceSession::class);
    }

    public function qrCode()
    {
        return $this->hasOne(QRCode::class);
    }
    public function news() {
    return $this->belongsToMany(News::class, 'flying_location_news');
}
// app/Models/FlyingLocation.php
// app/Models/FlyingLocation.php

public function activeSessions() {
    return $this->hasMany(AirspaceSession::class)
                ->where('status', 'active')
                ->whereNull('checked_out_at')
                ->where('expires_at', '>', now());
}
public function latestStatus()
{
    // هذه العلاقة تجلب لك آخر سجل حالة تم إضافته للموقع
    return $this->hasOne(ClearanceStatus::class)->latestOfMany();
}
}
