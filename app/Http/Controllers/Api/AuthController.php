<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PilotProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

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
        if (!$profile || $profile->user->phone !== $request->phone) {
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

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'user' => $user->load('pilotProfile'),
        'token' => $token,
    ]);
}
    // Register (Pilot)

public function register(Request $request)
{
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

        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    DB::beginTransaction();

    try {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_admin' => false,
        ]);

        $currentYear = now()->format('y');

        $clubCode = str_pad($request->club_code, 2, '0', STR_PAD_LEFT);

        $count = PilotProfile::where('club_code', $clubCode)->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $licenseNumber = "{$currentYear}-{$clubCode}-{$sequence}";

        $imagePath = null;

        if ($request->hasFile('image')) {

            $imagePath = $request->file('image')->store(
                'pilots/avatars',
                'public'
            );

            $imagePath = '/storage/' . $imagePath;
        }

        $profile = PilotProfile::create([
            'user_id' => $user->id,
            'license_number' => $licenseNumber,
            'blood_type' => $request->blood_type,
            'ratings' => implode(' | ', $request->ratings ?? []),
            'insurance_provider' => $request->insurance_provider,
            'insurance_number' => $request->insurance_number,
            'club_name' => $request->club_name,
            'club_code' => $clubCode,
            'designation' => 'Pilot',
            'valid_until' => now()->addYear(),

            'image' => $imagePath,
        ]);

        if ($request->disciplines) {
            $profile->disciplines()->sync($request->disciplines);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pilot registered successfully',
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
