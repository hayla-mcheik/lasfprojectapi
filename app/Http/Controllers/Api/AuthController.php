<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PilotProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
class AuthController extends Controller
{
    // Login
public function login(Request $request)
{
    // =========================================================
    // 1. PILOT LOGIN (License Number + Phone)
    // =========================================================
    if ($request->has('license_number')) {

        $request->validate([
            'license_number' => 'required',
            'phone' => 'required',
        ]);

        Log::info('PILOT LOGIN ATTEMPT', [
            'license_number' => $request->license_number,
            'phone' => $request->phone,
            'ip' => $request->ip(),
        ]);

        // Find pilot profile
        $profile = \App\Models\PilotProfile::where(
            'license_number',
            $request->license_number
        )->first();

        // ---------------------------------------------------------
        // License number not found
        // ---------------------------------------------------------
        if (!$profile) {

            Log::warning('PILOT LOGIN FAILED', [
                'license_number' => $request->license_number,
                'reason' => 'License number not found',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'License number or phone number is incorrect.',
            ], 401);
        }

        // ---------------------------------------------------------
        // Pilot profile exists but user account is missing
        // ---------------------------------------------------------
        if (!$profile->user) {

            Log::error('PILOT LOGIN FAILED', [
                'license_number' => $request->license_number,
                'profile_id' => $profile->id,
                'reason' => 'Pilot profile has no linked user account',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Your account is not properly configured. Please contact the administrator.',
            ], 500);
        }

        $user = $profile->user;

        // ---------------------------------------------------------
        // Phone does not match
        // ---------------------------------------------------------
        if ($user->phone !== $request->phone) {

            Log::warning('PILOT LOGIN FAILED', [
                'pilot_id' => $user->id,
                'license_number' => $request->license_number,
                'reason' => 'Phone number does not match',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'License number or phone number is incorrect.',
            ], 401);
        }

        // ---------------------------------------------------------
        // Pilot credentials are correct
        // ---------------------------------------------------------
        Log::info('PILOT CREDENTIALS VERIFIED', [
            'pilot_id' => $user->id,
            'pilot_name' => $user->name,
            'license_number' => $profile->license_number,
            'ip' => $request->ip(),
        ]);
    }

    // =========================================================
    // 2. ADMIN / STAFF LOGIN (Email + Password)
    // =========================================================
    else {

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        Log::info('ADMIN LOGIN ATTEMPT', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        $user = User::where('email', $request->email)->first();

        // ---------------------------------------------------------
        // Email doesn't exist
        // ---------------------------------------------------------
        if (!$user) {

            Log::warning('ADMIN LOGIN FAILED', [
                'email' => $request->email,
                'reason' => 'Email not found',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        // ---------------------------------------------------------
        // Password incorrect
        // ---------------------------------------------------------
        if (!Hash::check($request->password, $user->password)) {

            Log::warning('ADMIN LOGIN FAILED', [
                'user_id' => $user->id,
                'email' => $request->email,
                'reason' => 'Incorrect password',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        Log::info('ADMIN CREDENTIALS VERIFIED', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);
    }

    // =========================================================
    // 3. APPROVAL CHECK
    // =========================================================

    if (!$user->is_admin && !$user->is_approved) {

        Log::warning('LOGIN BLOCKED - ACCOUNT NOT APPROVED', [
            'user_id' => $user->id,
            'email' => $user->email,
            'license_number' => $user->pilotProfile?->license_number,
            'reason' => 'Account is not approved',
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Your membership application is awaiting administrator approval.',
        ], 403);
    }

    // =========================================================
    // 4. CREATE SANCTUM TOKEN
    // =========================================================

    try {

        $token = $user->createToken('api-token')->plainTextToken;

    } catch (\Throwable $e) {

        Log::error('LOGIN TOKEN CREATION FAILED', [
            'user_id' => $user->id,
            'email' => $user->email,
            'error' => $e->getMessage(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Unable to complete login at the moment. Please try again.',
        ], 500);
    }

    // =========================================================
    // 5. SUCCESS
    // =========================================================

    Log::info(
        $request->has('license_number')
            ? 'PILOT LOGIN SUCCESS'
            : 'ADMIN LOGIN SUCCESS',
        [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'email' => $user->email,
            'license_number' => $user->pilotProfile?->license_number,
            'ip' => $request->ip(),
        ]
    );

    return response()->json([
        'user' => $user->load('pilotProfile'),
        'token' => $token,
    ]);
}
    // Register (Pilot)

// Register (Pilot)
public function register(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | 1. Determine registration type
    |--------------------------------------------------------------------------
    |
    | Normal pilot:
    | is_international = false / 0
    |
    | International pilot:
    | is_international = true / 1
    |
    */

    $isInternational = $request->boolean('is_international');

    /*
    |--------------------------------------------------------------------------
    | 2. Validation
    |--------------------------------------------------------------------------
    */

    try {

        $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Common LASF account information
            |--------------------------------------------------------------------------
            */

            'name' => 'required|string|max:255',

            'email' => 'required|email|unique:users,email',

            'password' => 'required|min:6|confirmed',

            'phone' => 'required|string|max:20',

            'blood_type' => 'required|string|max:20',

            'ratings' => 'required|array|min:1',

            'disciplines' => 'required|array|min:1',

  'date_of_birth' => [
    'nullable',
    'date',
    'before:today',
],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'license_attachment' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:5120',
            ],

            'insurance_provider' => 'nullable|string|max:255',

            'insurance_number' => 'nullable|string|max:255',

            /*
            |--------------------------------------------------------------------------
            | Club
            |--------------------------------------------------------------------------
            |
            | Lebanese pilots must provide club code.
            | International pilots can use their foreign club name.
            |
            */

            'club_name' => 'required|string|max:255',

            'club_code' => $isInternational
                ? 'nullable|string|max:10'
                : 'required|string|max:10',

            /*
            |--------------------------------------------------------------------------
            | International Pilot Information
            |--------------------------------------------------------------------------
            */

            'first_name' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'father_name' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'last_name' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'mother_full_name' => 'nullable|string|max:255',

            'wife_full_name' => 'nullable|string|max:255',

            'place_of_birth' => $isInternational
                ? 'nullable|string|max:255'
                : 'nullable|string|max:255',

            'nationality' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'sex' => $isInternational
                ? 'required|string|max:50'
                : 'nullable|string|max:50',

            'marital_status' => $isInternational
                ? 'required|string|max:100'
                : 'nullable|string|max:100',

            'languages' => $isInternational
                ? 'required|string|max:500'
                : 'nullable|string|max:500',

            'national_id_number' => 'nullable|string|max:255',

            'file_number' => 'nullable|string|max:255',

            'passport_number' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'passport_issuing_authority' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'passport_issued_date' => $isInternational
                ? 'required|date'
                : 'nullable|date',

            'passport_expiry_date' => $isInternational
                ? 'required|date|after:passport_issued_date'
                : 'nullable|date',

            'mobile_phone' => 'nullable|string|max:50',

            'alternative_phone' => 'nullable|string|max:50',

            'work_email' => 'nullable|email|max:255',

            'personal_email' => 'nullable|email|max:255',

            /*
            |--------------------------------------------------------------------------
            | Current Address
            |--------------------------------------------------------------------------
            */

            'current_country' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'current_city' => $isInternational
                ? 'required|string|max:255'
                : 'nullable|string|max:255',

            'current_street_area' => 'nullable|string|max:255',

            'current_building' => 'nullable|string|max:255',

            'current_floor' => 'nullable|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Alternative Address
            |--------------------------------------------------------------------------
            */

            'alternative_country' => 'nullable|string|max:255',

            'alternative_city' => 'nullable|string|max:255',

            'alternative_street_area' => 'nullable|string|max:255',

            'alternative_building' => 'nullable|string|max:255',

            'alternative_floor' => 'nullable|string|max:100',

            /*
            |--------------------------------------------------------------------------
            | Employment
            |--------------------------------------------------------------------------
            */

            'employer_name' => 'nullable|string|max:255',

            'current_title' => 'nullable|string|max:255',

            /*
            |--------------------------------------------------------------------------
            | Criminal Record
            |--------------------------------------------------------------------------
            */

        'convicted_of_crime' => 'nullable|boolean',

'crime_type' => 'nullable|string|max:1000',

'crime_date' => 'nullable|date',
'visitor_name' => 'nullable|string|max:255',
            /*
            |--------------------------------------------------------------------------
            | International documents
            |--------------------------------------------------------------------------
            */

            'passport_document' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],

            'national_id_document' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png,webp',
                'max:10240',
            ],

            'signature' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],

        ], [

            'email.unique' =>
                'This email is already registered.',

            'password.confirmed' =>
                'Password confirmation does not match.',

            'date_of_birth.before' =>
                'Date of birth must be before today.',

            'passport_expiry_date.after' =>
                'Passport expiry date must be after the passport issued date.',

            'ratings.required' =>
                'Please select at least one rating.',

            'disciplines.required' =>
                'Please select at least one discipline.',

            'passport_number.required' =>
                'Passport number is required for international pilots.',

            'passport_issuing_authority.required' =>
                'Passport issuing authority is required for international pilots.',

            'passport_issued_date.required' =>
                'Passport issued date is required for international pilots.',

            'passport_expiry_date.required' =>
                'Passport expiry date is required for international pilots.',

        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors(),
        ], 422);
    }


    /*
    |--------------------------------------------------------------------------
    | 3. Start database transaction
    |--------------------------------------------------------------------------
    */

    DB::beginTransaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | 4. Create User
        |--------------------------------------------------------------------------
        */

        $user = User::create([

            'name' => $request->name,

            'email' => $request->email,

            'phone' => $request->phone,

            'password' => Hash::make($request->password),

            'is_admin' => false,

            'is_active' => false,

            'is_approved' => false,

        ]);


        /*
        |--------------------------------------------------------------------------
        | 5. Generate LASF License Number
        |--------------------------------------------------------------------------
        |
        | Normal Lebanese pilot:
        |
        | 26-01-0001
        |
        | International pilot:
        |
        | 26-99-0001
        |
        | 99 is reserved for international pilots.
        |
        */

        $currentYear = now()->format('y');

        if ($isInternational) {

            // Reserved club code for international pilots
            $clubCode = '99';

        } else {

            $clubCode = str_pad(
                $request->club_code,
                2,
                '0',
                STR_PAD_LEFT
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 6. Find last license number
        |--------------------------------------------------------------------------
        */

        $lastLicense = PilotProfile::where(
            'club_code',
            $clubCode
        )
            ->where(
                'license_number',
                'like',
                $currentYear . '-' . $clubCode . '-%'
            )
            ->orderBy('id', 'desc')
            ->value('license_number');


        if ($lastLicense) {

            $parts = explode('-', $lastLicense);

            $lastSequence = isset($parts[2])
                ? (int) $parts[2]
                : 0;

            $sequence = str_pad(
                $lastSequence + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

        } else {

            $sequence = '0001';
        }


        $licenseNumber =
            "{$currentYear}-{$clubCode}-{$sequence}";


        /*
        |--------------------------------------------------------------------------
        | 7. Upload Pilot Image
        |--------------------------------------------------------------------------
        */

        $imagePath = null;

        if ($request->hasFile('image')) {

            $imagePath = $request->file('image')->store(
                'pilots/avatars',
                'public'
            );

            $imagePath = '/storage/' . $imagePath;
        }


        /*
        |--------------------------------------------------------------------------
        | 8. Upload LASF License
        |--------------------------------------------------------------------------
        */

        $licenseAttachment = null;

        if ($request->hasFile('license_attachment')) {

            $licenseAttachment = $request->file(
                'license_attachment'
            )->store(
                'pilots/licenses',
                'public'
            );

            $licenseAttachment =
                '/storage/' . $licenseAttachment;
        }


        /*
        |--------------------------------------------------------------------------
        | 9. Create Pilot Profile
        |--------------------------------------------------------------------------
        */

        $profile = PilotProfile::create([

            'user_id' => $user->id,

            'is_international' => $isInternational,

            'license_number' => $licenseNumber,

            'date_of_birth' => $request->date_of_birth,

            'blood_type' => $request->blood_type,

            'ratings' => implode(
                ' | ',
                $request->ratings ?? []
            ),

            'insurance_provider' =>
                $request->insurance_provider,

            'insurance_number' =>
                $request->insurance_number,

            'club_name' =>
                $request->club_name,

            'club_code' =>
                $clubCode,

            'designation' => 'Pilot',

            'valid_until' =>
                now()->addYear(),

            'image' => $imagePath,

            'licenses_attachments' =>
                $licenseAttachment
                    ? [$licenseAttachment]
                    : null,

        ]);


        /*
        |--------------------------------------------------------------------------
        | 10. Attach Disciplines
        |--------------------------------------------------------------------------
        */

        if ($request->disciplines) {

            $profile->disciplines()->sync(
                $request->disciplines
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 11. International Pilot Details
        |--------------------------------------------------------------------------
        */

        if ($isInternational) {

            /*
            |--------------------------------------------------------------------------
            | Upload Passport Document
            |--------------------------------------------------------------------------
            */

            $passportDocumentPath = null;

            if ($request->hasFile('passport_document')) {

                $passportDocumentPath =
                    $request->file(
                        'passport_document'
                    )->store(
                        'pilots/international/passports',
                        'public'
                    );

                $passportDocumentPath =
                    '/storage/' . $passportDocumentPath;
            }


            /*
            |--------------------------------------------------------------------------
            | Upload National ID
            |--------------------------------------------------------------------------
            */

            $nationalIdDocumentPath = null;

            if ($request->hasFile('national_id_document')) {

                $nationalIdDocumentPath =
                    $request->file(
                        'national_id_document'
                    )->store(
                        'pilots/international/national-ids',
                        'public'
                    );

                $nationalIdDocumentPath =
                    '/storage/' . $nationalIdDocumentPath;
            }


            /*
            |--------------------------------------------------------------------------
            | Upload Signature
            |--------------------------------------------------------------------------
            */

            $signaturePath = null;

            if ($request->hasFile('signature')) {

                $signaturePath =
                    $request->file(
                        'signature'
                    )->store(
                        'pilots/international/signatures',
                        'public'
                    );

                $signaturePath =
                    '/storage/' . $signaturePath;
            }


            /*
            |--------------------------------------------------------------------------
            | Create International Details
            |--------------------------------------------------------------------------
            */

            $profile->internationalDetails()->create([

                'first_name' =>
                    $request->first_name,

                'father_name' =>
                    $request->father_name,

                'last_name' =>
                    $request->last_name,

                'mother_full_name' =>
                    $request->mother_full_name,

                'wife_full_name' =>
                    $request->wife_full_name,

                /*
                | Use the same DOB as the normal LASF profile
                */
                'date_of_birth' =>
                    $request->date_of_birth,

                'place_of_birth' =>
                    $request->place_of_birth,

                'nationality' =>
                    $request->nationality,

                'sex' =>
                    $request->sex,

                'marital_status' =>
                    $request->marital_status,

                'languages' =>
                    $request->languages,

                'national_id_number' =>
                    $request->national_id_number,

                'file_number' =>
                    $request->file_number,

                'passport_number' =>
                    $request->passport_number,

                'passport_issuing_authority' =>
                    $request->passport_issuing_authority,

                'passport_issued_date' =>
                    $request->passport_issued_date,

                'passport_expiry_date' =>
                    $request->passport_expiry_date,

                'mobile_phone' =>
                    $request->mobile_phone,

                'alternative_phone' =>
                    $request->alternative_phone,

                'work_email' =>
                    $request->work_email,

                'personal_email' =>
                    $request->personal_email,

                /*
                |--------------------------------------------------------------------------
                | Current Address
                |--------------------------------------------------------------------------
                */

                'current_country' =>
                    $request->current_country,

                'current_city' =>
                    $request->current_city,

                'current_street_area' =>
                    $request->current_street_area,

                'current_building' =>
                    $request->current_building,

                'current_floor' =>
                    $request->current_floor,

                /*
                |--------------------------------------------------------------------------
                | Alternative Address
                |--------------------------------------------------------------------------
                */

                'alternative_country' =>
                    $request->alternative_country,

                'alternative_city' =>
                    $request->alternative_city,

                'alternative_street_area' =>
                    $request->alternative_street_area,

                'alternative_building' =>
                    $request->alternative_building,

                'alternative_floor' =>
                    $request->alternative_floor,

                /*
                |--------------------------------------------------------------------------
                | Employment
                |--------------------------------------------------------------------------
                */

                'employer_name' =>
                    $request->employer_name,

                'current_title' =>
                    $request->current_title,

                /*
                |--------------------------------------------------------------------------
                | Criminal Record
                |--------------------------------------------------------------------------
                */

                'convicted_of_crime' =>
                    $request->boolean('convicted_of_crime'),

                'crime_type' =>
                    $request->crime_type,

'crime_date' =>
    $request->crime_date,

'visitor_name' =>
    $request->visitor_name,

                /*
                |--------------------------------------------------------------------------
                | Uploaded Documents
                |--------------------------------------------------------------------------
                */

                'passport_document' =>
                    $passportDocumentPath,

                'national_id_document' =>
                    $nationalIdDocumentPath,

                'signature' =>
                    $signaturePath,

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | 12. Email Admin
        |--------------------------------------------------------------------------
        */

        try {

            \Mail::raw(

                "New LASF Membership Request\n\n" .

                "Name: {$user->name}\n" .

                "Email: {$user->email}\n" .

                "Phone: {$user->phone}\n" .

                "License Number: {$licenseNumber}\n" .

                "Club: {$request->club_name}\n" .

                "Pilot Type: " .
                ($isInternational
                    ? 'International Pilot'
                    : 'Lebanese Pilot') .
                "\n\n" .

                "This member is waiting for approval.",

                function ($message) use ($user) {

                    $message
                        ->to('mikel.c.khalil@gmail.com')
                        ->subject(
                            'New LASF Membership Request - ' .
                            $user->name
                        );
                }
            );

        } catch (\Exception $mailError) {

            \Log::error(
                'Admin email failed: ' .
                $mailError->getMessage()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 13. Email Pilot Confirmation
        |--------------------------------------------------------------------------
        */

        try {

            \Mail::raw(

                "Dear {$user->name},\n\n" .

                "Thank you for registering with LASF.\n\n" .

                "Your membership application has been received " .
                "and is awaiting administrator approval.\n\n" .

                "License Number: {$licenseNumber}\n\n" .

                "You will be able to login once your account " .
                "has been approved by the administrator.",

                function ($message) use ($user) {

                    $message
                        ->to($user->email)
                        ->subject(
                            'LASF Registration Received'
                        );
                }
            );

        } catch (\Exception $mailError) {

            \Log::error(
                'Pilot email failed: ' .
                $mailError->getMessage()
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 14. Commit
        |--------------------------------------------------------------------------
        */

        DB::commit();


        /*
        |--------------------------------------------------------------------------
        | 15. Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'Registration submitted successfully. ' .
                'Your membership is waiting for administrator approval.',

            'license_number' =>
                $licenseNumber,

            'image' =>
                $imagePath,

            'is_international' =>
                $isInternational,

        ], 201);


    } catch (\Exception $e) {

        /*
        |--------------------------------------------------------------------------
        | Rollback database transaction
        |--------------------------------------------------------------------------
        */

        DB::rollBack();

        \Log::error(
            'Pilot registration failed',
            [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email ?? null,
            ]
        );

        return response()->json([

            'success' => false,

            'message' =>
                'Unable to complete registration. Please try again.',

            'error' =>
                config('app.debug')
                    ? $e->getMessage()
                    : null,

        ], 500);
    }
}
public function myMembership(Request $request)
{
    return response()->json([
        'user' => $request->user()->load([
            'pilotProfile.disciplines'
        ])
    ]);
}
public function updateMembership(Request $request)
{
$request->validate([
    'name' => 'required|string|max:255',
    'phone' => 'required|string|max:20',
    'date_of_birth' => 'nullable|date|before:today',
    'blood_type' => 'required',

    'disciplines' => 'required|array|min:1',
    'disciplines.*' => 'exists:sports,id',

    'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    'license_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
]);

    $user = $request->user();

    $user->update([
        'name' => $request->name,
        'phone' => $request->phone,
    ]);

    $profileData = [
        'blood_type' => $request->blood_type,
        'insurance_provider' => $request->insurance_provider,
        'insurance_number' => $request->insurance_number,
        'facebook_url' => $request->facebook_url,
        'instagram_url' => $request->instagram_url,
        'date_of_birth' => $request->date_of_birth,
    ];

    if ($request->hasFile('license_attachment')) {

    if (!empty($user->pilotProfile->licenses_attachments)) {

        foreach ($user->pilotProfile->licenses_attachments as $oldFile) {

            Storage::disk('public')->delete(
                str_replace('/storage/', '', $oldFile)
            );
        }
    }

    $path = $request->file('license_attachment')->store(
        'pilots/licenses',
        'public'
    );

    $profileData['licenses_attachments'] = [
        '/storage/' . $path
    ];
}

if ($request->hasFile('image')) {

    $path = $request->file('image')->store(
        'pilots/avatars',
        'public'
    );

    $profileData['image'] = '/storage/' . $path;
}

$user->pilotProfile()->update($profileData);

$user->pilotProfile->disciplines()->sync(
    $request->disciplines
);

return response()->json([
    'success' => true,
    'message' => 'Membership updated successfully.',
    'user' => $user->load('pilotProfile.disciplines')
]);
}


    public function updateProfile(Request $request)
{
    $user = $request->user();

    $request->validate([
        'name'  => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,' . $user->id,
        'current_password' => 'nullable|required_with:new_password',
        'new_password'     => 'nullable|min:6|confirmed',
    ]);

    // Update basic info
    $user->name = $request->name;
    $user->email = $request->email;

    // Handle password change if requested
    if ($request->filled('new_password')) {
        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }
        $user->password = Hash::make($request->new_password);
    }

    $user->save();

    return response()->json([
        'message' => 'Profile updated successfully',
        'user' => $user
    ]);
}

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
