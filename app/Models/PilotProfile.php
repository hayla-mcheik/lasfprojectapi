<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PilotProfile extends Model
{
    use HasFactory;

    // Define fillable parameters ensuring 'license_number' stays untouched
    protected $fillable = [
        'user_id',
        'license_number', // Exact Excel table naming convention preserved
        'blood_type',
        'ratings',
        'insurance_provider',
        'insurance_number',
        'club_name',
        'club_code',
        'facebook_url',  // Kept and nullable
        'instagram_url', // Kept and nullable
        'designation',
        'image',
        'licenses_attachments',
        'valid_until',
    ];

    /**
     * Cast JSON array blocks back to PHP arrays automatically.
     */
    protected $casts = [
        'licenses_attachments' => 'array',
        'valid_until' => 'date',
    ];

    /**
     * Relationship with the core User account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with the sports/disciplines data matrix.
     * Maps to the pivot table that replaced the old license_type logic.
     */
    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(Sport::class, 'pilot_profile_sport', 'pilot_profile_id', 'sport_id')
                    ->withTimestamps();
    }
}