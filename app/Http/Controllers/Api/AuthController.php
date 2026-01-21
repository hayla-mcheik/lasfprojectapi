<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PilotProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

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
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'phone' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'is_admin' => false,
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
