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
                'password' => bcrypt('password'), // Default password
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
        // FIX: Added ignore rule for unique constraints to prevent 422 errors when saving unchanged data
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $pilot->id,
            'phone' => 'nullable|string|max:20',
    
            'license_number' => 'required|string|max:50|unique:pilot_profiles,license_number,' . ($pilot->pilotProfile->id ?? 0),
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
            // Update User (Includes Phone)
            $pilot->update($request->only(['name', 'email', 'phone', 'is_active']));

            $profileData = $request->only(['license_number', 'license_type', 'club_name', 'expiry_date', 'facebook_url', 'instagram_url', 'designation']);

            if ($request->hasFile('image')) {
                // Delete old image if it exists
                if ($pilot->pilotProfile && $pilot->pilotProfile->image) {
                    $oldPath = str_replace('/storage/', '', $pilot->pilotProfile->image);
                    Storage::disk('public')->delete($oldPath);
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
        if ($pilot->is_admin) {
            return response()->json(['success' => false, 'message' => 'Cannot delete an administrator'], 403);
        }

        DB::beginTransaction();
        try {
            if ($pilot->pilotProfile) {
                // Delete photo from storage
                if ($pilot->pilotProfile->image) {
                    $imagePath = str_replace('/storage/', '', $pilot->pilotProfile->image);
                    Storage::disk('public')->delete($imagePath);
                }
                $pilot->pilotProfile()->delete();
            }

            // Optional: Handle related airspace sessions if they exist
            if (method_exists($pilot, 'airspaceSessions')) {
                $pilot->airspaceSessions()->delete();
            }

            $pilot->delete();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pilot deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

public function export(Request $request)
    {
        $pilots = User::with('pilotProfile')->where('is_admin', false)->get();
        
        $fileName = 'pilots_export_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Name', 'Email', 'Phone', 'License Number', 'License Type', 'Designation', 'Club', 'Status'];

        $callback = function() use($pilots, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($pilots as $pilot) {
                fputcsv($file, [
                    $pilot->name,
                    $pilot->email,
                    $pilot->phone,
                    $pilot->pilotProfile->license_number ?? 'N/A',
                    $pilot->pilotProfile->license_type ?? 'N/A',
                    $pilot->pilotProfile->designation ?? 'N/A',
                    $pilot->pilotProfile->club_name ?? 'N/A',
                    $pilot->is_active ? 'Active' : 'Inactive',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please upload a valid CSV file.'], 422);
        }

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        fgetcsv($handle); // Skip header row

        $count = 0;
        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // $data: 0=Name, 1=Email, 2=Phone, 3=License#, 4=Type, 5=Designation
                if (empty($data[1])) continue;

                $user = User::updateOrCreate(
                    ['email' => $data[1]],
                    [
                        'name' => $data[0],
                        'phone' => $data[2] ?? null,
                        'password' => bcrypt('password'),
                        'is_active' => true
                    ]
                );

                $user->pilotProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'license_number' => $data[3],
                        'license_type' => $data[4] ?? 'paragliding',
                        'designation' => $data[5] ?? null,
                    ]
                );
                $count++;
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => "Successfully imported $count pilots."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }
}