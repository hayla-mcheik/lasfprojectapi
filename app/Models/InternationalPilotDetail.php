<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InternationalPilotDetail extends Model
{
    protected $fillable = [
        'pilot_profile_id',

        // Personal Information
        'first_name',
        'father_name',
        'last_name',
        'mother_full_name',
        'wife_full_name',

        'date_of_birth',
        'place_of_birth',
        'nationality',
        'sex',
        'marital_status',

        // Languages
        'languages',

        // Identification
        'national_id_number',
        'file_number',

        // Passport
        'passport_number',
        'passport_issuing_authority',
        'passport_issued_date',
        'passport_expiry_date',

        // Phones / Emails
        'mobile_phone',
        'alternative_phone',
        'work_email',
        'personal_email',

        // Current Address
        'current_country',
        'current_city',
        'current_street_area',
        'current_building',
        'current_floor',

        // Alternative Address
        'alternative_country',
        'alternative_city',
        'alternative_street_area',
        'alternative_building',
        'alternative_floor',

        // Employment
        'employer_name',
        'current_title',

        // Criminal Record
        'convicted_of_crime',
        'crime_type',
        'crime_date',

        // Visitor
        'visitor_name',

        // Signature
        'signature',

        // Documents
        'passport_document',
        'national_id_document',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_issued_date' => 'date',
        'passport_expiry_date' => 'date',
        'crime_date' => 'date',
        'convicted_of_crime' => 'boolean',
    ];

    public function pilotProfile()
    {
        return $this->belongsTo(PilotProfile::class);
    }
}