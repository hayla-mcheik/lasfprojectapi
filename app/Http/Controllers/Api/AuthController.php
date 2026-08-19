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
class AuthController extends Controller
{
    // Login
public function login(Request $request)
{
    // 1. PILOT LOGIN (License + Phone)
    if ($request->has('license_number')) {
        $request->validate([
            'license_number' => 'required',
            'phone' => 'required', // Use phone as the "password"
        ]);

        $profile = \App\Models\PilotProfile::where('license_number', $request->license_number)->first();
        
        // Verify profile exists and the linked user's phone matches
        if (
    !$profile ||
    !$profile->user ||
    $profile->user->phone !== $request->phone
) {
            return response()->json(['message' => 'License Number or Phone does not match our records.'], 401);
        }
        
        $user = $profile->user;
    } 
    // 2. ADMIN LOGIN (Standard Email + Password)
    else {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Admin credentials invalid'], 401);
        }
    }
if (!$user->is_admin && !$user->is_approved) {

    return response()->json([
        'message' => 'Your membership application is awaiting administrator approval.'
    ], 403);

}

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'user' => $user->load('pilotProfile'),
        'token' => $token,
    ]);
}
    // Register (Pilot)

public function register(Request $request)
{
    try {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'phone' => 'required|string|max:20',

            'blood_type' => 'required',
            'club_name' => 'required',
            'club_code' => 'required',

            'insurance_provider' => 'nullable|string',
            'insurance_number' => 'nullable|string',

            'ratings' => 'required|array',
            'disciplines' => 'required|array',

            'date_of_birth' => 'nullable|date|before:today',

            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'license_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ], [
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'Password confirmation does not match.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'ratings.required' => 'Please select at least one rating.',
            'disciplines.required' => 'Please select at least one discipline.',
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {

        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),

            'is_admin' => false,
            'is_active' => false,
            'is_approved' => false,
        ]);
$currentYear = now()->format('y');

$clubCode = str_pad($request->club_code, 2, '0', STR_PAD_LEFT);

$lastLicense = PilotProfile::where('club_code', $clubCode)
    ->where('license_number', 'like', $currentYear . '-' . $clubCode . '-%')
    ->orderBy('id', 'desc')
    ->value('license_number');

if ($lastLicense) {

    $parts = explode('-', $lastLicense);

    $lastSequence = (int) $parts[2];

    $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);

} else {

    $sequence = '0001';
}

$licenseNumber = "{$currentYear}-{$clubCode}-{$sequence}";

        $imagePath = null;

        if ($request->hasFile('image')) {

            $imagePath = $request->file('image')->store(
                'pilots/avatars',
                'public'
            );

            $imagePath = '/storage/' . $imagePath;
        }

        $licenseAttachment = null;

if ($request->hasFile('license_attachment')) {

    $licenseAttachment = $request->file('license_attachment')->store(
        'pilots/licenses',
        'public'
    );

    $licenseAttachment = '/storage/' . $licenseAttachment;
}

        $profile = PilotProfile::create([
            'user_id' => $user->id,
            'license_number' => $licenseNumber,

            'date_of_birth' => $request->date_of_birth,

            'blood_type' => $request->blood_type,
            'ratings' => implode(' | ', $request->ratings ?? []),

            'insurance_provider' => $request->insurance_provider,
            'insurance_number' => $request->insurance_number,

            'club_name' => $request->club_name,
            'club_code' => $clubCode,

            'designation' => 'Pilot',
            'valid_until' => now()->addYear(),

            'image' => $imagePath,
          'licenses_attachments' => $licenseAttachment
    ? [$licenseAttachment]
    : null,
        ]);

        if ($request->disciplines) {
            $profile->disciplines()->sync($request->disciplines);
        }

        /*
        |--------------------------------------------------------------------------
        | Email Admin
        |--------------------------------------------------------------------------
        */

        try {

            \Mail::raw(
                "New LASF Membership Request\n\n" .
                "Name: {$user->name}\n" .
                "Email: {$user->email}\n" .
                "Phone: {$user->phone}\n" .
                "License Number: {$licenseNumber}\n" .
                "Club: {$request->club_name}\n\n" .
                "This member is waiting for approval.",
                function ($message) use ($user) {

                    $message->to('mikel.c.khalil@gmail.com')
                        ->subject('New LASF Membership Request - ' . $user->name);

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
        | Email Pilot Confirmation
        |--------------------------------------------------------------------------
        */

        try {

            \Mail::raw(
                "Dear {$user->name},\n\n" .
                "Thank you for registering with LASF.\n\n" .
                "Your membership application has been received and is awaiting administrator approval.\n\n" .
                "License Number: {$licenseNumber}\n\n" .
                "You will be able to login once your account has been approved by the administrator.",
                function ($message) use ($user) {

                    $message->to($user->email)
                        ->subject('LASF Registration Received');

                }
            );

        } catch (\Exception $mailError) {

            \Log::error(
                'Pilot email failed: ' .
                $mailError->getMessage()
            );

        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Registration submitted successfully. Your membership is waiting for administrator approval.',
            'license_number' => $licenseNumber,
            'image' => $imagePath
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
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
