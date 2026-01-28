<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PilotProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PilotController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('pilotProfile')->where('is_admin', false);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('pilotProfile', function($q) use ($search) {
                      $q->where('license_number', 'like', "%{$search}%")
                        ->orWhere('club_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = $request->per_page ?? 20;
        return response()->json($query->latest()->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
      'license_number' => 'required|string|max:50|unique:pilot_profiles,license_number',
            'license_type' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
            'club_name' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'designation' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt('password'),
                'is_admin' => false,
                'is_active' => $request->is_active ?? true,
            ]);

            $profileData = $request->only(['license_number', 'license_type', 'expiry_date', 'club_name', 'facebook_url', 'instagram_url', 'designation']);
            
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('pilots', 'public');
                $profileData['image'] = Storage::url($path);
            }

            $user->pilotProfile()->create($profileData);

            DB::commit();
            return response()->json(['success' => true, 'data' => $user->load('pilotProfile')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, User $pilot)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $pilot->id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
         'license_number' => 'required|string|max:50|unique:pilot_profiles,license_number',
            'license_type' => 'nullable|string|max:50',
            'club_name' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url',
            'instagram_url' => 'nullable|url',
            'designation' => 'nullable|string|max:100',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $pilot->update($request->only(['name', 'email', 'phone', 'is_active']));

            $profileData = $request->only(['license_number', 'license_type', 'club_name', 'expiry_date', 'facebook_url', 'instagram_url', 'designation']);

            if ($request->hasFile('image')) {
                if ($pilot->pilotProfile && $pilot->pilotProfile->image) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $pilot->pilotProfile->image));
                }
                $path = $request->file('image')->store('pilots', 'public');
                $profileData['image'] = Storage::url($path);
            }

            $pilot->pilotProfile()->updateOrCreate(['user_id' => $pilot->id], $profileData);

            DB::commit();
            return response()->json(['success' => true, 'data' => $pilot->load('pilotProfile')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

public function destroy(User $pilot)
{
    // 1. Safety check: Don't let an admin delete themselves or other admins via this route
    if ($pilot->is_admin) {
        return response()->json(['message' => 'Cannot delete an administrator'], 403);
    }

    try {
        // Use a transaction so if one part fails, nothing is deleted
        return DB::transaction(function () use ($pilot) {
            
            // 2. Delete the Pilot Profile first (Line 140 likely fails because of this)
            if ($pilot->pilotProfile) {
                // Delete photo from storage if it exists
                if ($pilot->pilotProfile->image) {
                    $imagePath = str_replace('/storage/', '', $pilot->pilotProfile->image);
                    Storage::disk('public')->delete($imagePath);
                }
                $pilot->pilotProfile->delete();
            }

            // 3. Delete related airspace sessions (if you don't mind losing flight history)
            // If you want to keep history, you'd need to set pilot_id to null or use SoftDeletes
            $pilot->airspaceSessions()->delete();

            // 4. Now it is safe to delete the User
            $pilot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pilot and all related data deleted successfully'
            ]);
        });
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Delete failed: ' . $e->getMessage()
        ], 500);
    }
}

    public function export(Request $request)
    {
        $pilots = User::with('pilotProfile')
            ->where('is_admin', false)
            ->where('is_active', true)
            ->get()
            ->map(function($pilot) {
                return [
                    'Name' => $pilot->name,
                    'Email' => $pilot->email,
                    'Phone' => $pilot->phone,
                    'License Number' => $pilot->pilotProfile->license_number ?? 'N/A',
                    'License Type' => $pilot->pilotProfile->license_type ?? 'N/A',
                    'Issued By' => $pilot->pilotProfile->issued_by ?? 'N/A',
                    'Expiry Date' => $pilot->pilotProfile->expiry_date ?? 'N/A',
                    'Club' => $pilot->pilotProfile->club_name ?? 'N/A',
                    'Registered' => $pilot->created_at->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pilots,
            'total' => $pilots->count()
        ]);
    }
}