<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PilotProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PilotController extends Controller
{
    public function index(Request $request)
    {
        // Updated relation call to match camelCase method name: pilotProfile
        $query = User::with(['pilotProfile.disciplines'])->where('is_admin', false);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('pilotProfile', function($q) use ($search) {
                      $q->where('license_number', 'like', "%{$search}%")
                        ->orWhere('club_name', 'like', "%{$search}%")
                        ->orWhere('ratings', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status !== null) {
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
            'date_of_birth' => 'required|date',
            'blood_type' => 'required|string|max:5',
            'club_name' => 'required|string|max:100',
            'club_code' => 'required|string|max:10',
            'insurance_provider' => 'nullable|string|max:150',
            'insurance_number' => 'nullable|string|max:100',
            'designation' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'disciplines' => 'required|array',
            'ratings' => 'required|array',
            'image' => 'nullable|image|max:2048',
            'licenses.*' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:4096'
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

            $currentYear = Carbon::now()->format('y'); 
            $paddedClubCode = str_pad($request->club_code, 2, '0', STR_PAD_LEFT); 
            
            $clubSequenceCount = PilotProfile::where('club_code', $paddedClubCode)->count();
            $sequence = str_pad($clubSequenceCount + 1, 4, '0', STR_PAD_LEFT); 
            $generatedLicenseNumber = "{$currentYear}-{$paddedClubCode}-{$sequence}";

            $avatarUrl = null;
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('pilots/avatars', 'public');
                $avatarUrl = Storage::url($path);
            }

            $licenseAttachments = [];
            if ($request->hasFile('licenses')) {
                foreach ($request->file('licenses') as $file) {
                    $licenseAttachments[] = $file->store('pilots/licenses', 'public');
                }
            }

            // Updated relation builder call to use camelCase function: pilotProfile()
            $profile = $user->pilotProfile()->create([
                'license_number' => $generatedLicenseNumber,
                'blood_type' => $request->blood_type,
                'ratings' => implode(' | ', $request->ratings),
                'insurance_provider' => $request->insurance_provider,
                'insurance_number' => $request->insurance_number,
                'club_name' => $request->club_name,
                'club_code' => $paddedClubCode,
                'facebook_url' => $request->facebook_url,
                'instagram_url' => $request->instagram_url,
                'designation' => $request->designation ?? 'Professional Pilot',
                'image' => $avatarUrl,
                'licenses_attachments' => $licenseAttachments,
                'valid_until' => Carbon::now()->addYear(),
                'date_of_birth' => $request->date_of_birth,
            ]);

            $profile->disciplines()->sync($request->disciplines);

            DB::commit();
            return response()->json(['success' => true, 'data' => $user->load('pilotProfile.disciplines')], 201);
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
            'blood_type' => 'required|string|max:5',
            'club_name' => 'required|string|max:100',
            'club_code' => 'required|string|max:10',
            'insurance_provider' => 'nullable|string|max:150',
            'insurance_number' => 'nullable|string|max:100',
            'designation' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'disciplines' => 'required|array',
            'ratings' => 'required|array',
            'image' => 'nullable|image|max:2048',
            'licenses.*' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:4096',
            'date_of_birth' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $pilot->update($request->only(['name', 'email', 'phone', 'is_active']));
            
            // Updated property call to camelCase relation: pilotProfile
            $profile = $pilot->pilotProfile;

            $oldClubCode = $profile?->club_code;
            $newClubCode = str_pad($request->club_code, 2, '0', STR_PAD_LEFT);
            $licenseNumber = $profile?->license_number;

            if ($oldClubCode !== $newClubCode) {
                $currentYear = Carbon::now()->format('y');
                $clubSequenceCount = PilotProfile::where('club_code', $newClubCode)->count();
                $sequence = str_pad($clubSequenceCount + 1, 4, '0', STR_PAD_LEFT);
                $licenseNumber = "{$currentYear}-{$newClubCode}-{$sequence}";
            }

            $profileData = [
                'license_number' => $licenseNumber,
                'blood_type' => $request->blood_type,
                'ratings' => implode(' | ', $request->ratings),
                'insurance_provider' => $request->insurance_provider,
                'insurance_number' => $request->insurance_number,
                'club_name' => $request->club_name,
                'club_code' => $newClubCode,
                'facebook_url' => $request->facebook_url,
                'instagram_url' => $request->instagram_url,
                'designation' => $request->designation,
                'date_of_birth' => $request->date_of_birth,
            ];

            if ($request->hasFile('image')) {
                if ($profile && $profile->image) {
                    $oldPath = str_replace('/storage/', '', $profile->image);
                    Storage::disk('public')->delete($oldPath);
                }
                $path = $request->file('image')->store('pilots/avatars', 'public');
                $profileData['image'] = Storage::url($path);
            }

            if ($request->hasFile('licenses')) {
                $existingFiles = $profile?->licenses_attachments ?? [];
                foreach ($request->file('licenses') as $file) {
                    $existingFiles[] = $file->store('pilots/licenses', 'public');
                }
                $profileData['licenses_attachments'] = $existingFiles;
            }

            // Updated method call to use camelCase relation builder: pilotProfile()
            $updatedProfile = $pilot->pilotProfile()->updateOrCreate(['user_id' => $pilot->id], $profileData);
            $updatedProfile->disciplines()->sync($request->disciplines);

            DB::commit();
            return response()->json(['success' => true, 'data' => $pilot->load('pilotProfile.disciplines')]);
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
            // Updated property call to camelCase relation: pilotProfile
            $profile = $pilot->pilotProfile;
            if ($profile) {
                if ($profile->image) {
                    $imagePath = str_replace('/storage/', '', $profile->image);
                    Storage::disk('public')->delete($imagePath);
                }
                if (!empty($profile->licenses_attachments)) {
                    foreach ($profile->licenses_attachments as $filePath) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
                $profile->disciplines()->detach();
                $profile->delete();
            }

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
public function approve(User $pilot)
{
    $pilot->update([
        'is_approved' => true,
        'is_active' => true,
    ]);

    try {

        Mail::raw(
            "Dear {$pilot->name},\n\n" .
            "Your LASF membership has been approved.\n\n" .
            "You may now login to your account.",
            function ($message) use ($pilot) {

                $message->to($pilot->email)
                    ->subject('LASF Membership Approved');

            }
        );

    } catch (\Exception $e) {

        \Log::error(
            'Approval email failed: ' .
            $e->getMessage()
        );

    }

    return response()->json([
        'success' => true,
        'message' => 'Member approved successfully.'
    ]);
}

public function reject(User $pilot)
{
    $pilot->update([
        'is_approved' => false,
        'is_active' => false,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Member rejected successfully.'
    ]);
}


    public function export(Request $request)
    {
        // Updated relation tracking keyword to use camelCase: pilotProfile
        $pilots = User::with(['pilotProfile'])->where('is_admin', false)->get();
        
        $fileName = 'pilots_registry_export_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Name', 'Email', 'Phone', 'Member License Number', 'Blood Type', 'Ratings', 'Insurance Provider', 'Insurance Number', 'Club Name', 'Club Code', 'Designation', 'Facebook', 'Instagram', 'Valid Until', 'Status'];

        $callback = function() use($pilots, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($pilots as $pilot) {
                // Updated structural property check to use camelCase: pilotProfile
                $prof = $pilot->pilotProfile;
                fputcsv($file, [
                    $pilot->name,
                    $pilot->email,
                    $pilot->phone,
                    $prof->license_number ?? 'Pending',
                    $prof->blood_type ?? 'N/A',
                    $prof->ratings ?? 'None',
                    $prof->insurance_provider ?? 'N/A',
                    $prof->insurance_number ?? 'N/A',
                    $prof->club_name ?? 'N/A',
                    $prof->club_code ?? 'N/A',
                    $prof->designation ?? 'Professional Pilot',
                    $prof->facebook_url ?? '',
                    $prof->instagram_url ?? '',
                    $prof ? ($prof->valid_until ? $prof->valid_until->format('d/m/Y') : '') : '',
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
        fgetcsv($handle); // Skip column descriptions header

        $count = 0;
        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
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

                // Updated collection model query writer function to use camelCase: pilotProfile()
                $user->pilotProfile()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'license_number' => !empty($data[3]) ? $data[3] : Carbon::now()->format('y') . '-' . str_pad($data[9] ?? '01', 2, '0', STR_PAD_LEFT) . '-' . str_pad(rand(1,999), 4, '0', STR_PAD_LEFT),
                        'blood_type' => $data[4] ?? 'O+',
                        'ratings' => $data[5] ?? 'None',
                        'insurance_provider' => $data[6] ?? null,
                        'insurance_number' => $data[7] ?? null,
                        'club_name' => $data[8] ?? 'Thermique',
                        'club_code' => str_pad($data[9] ?? '01', 2, '0', STR_PAD_LEFT),
                        'designation' => $data[10] ?? 'Professional Pilot',
                        'valid_until' => Carbon::now()->addYear()
                    ]
                );
                $count++;
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => "Successfully imported $count pilots records into database structures."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Import operational synchronization anomaly: ' . $e->getMessage()], 500);
        }
    }

public function licenses(User $pilot)
{
    $profile = $pilot->pilotProfile;

    if (!$profile) {
        return response()->json([]);
    }

return response()->json(
    collect($profile->licenses_attachments ?? [])
        ->map(function ($file, $index) use ($pilot) {

            return [
                'index' => $index,
                'name' => basename($file),
                'view' => asset($file),
     'download' => url("/admin/pilots/{$pilot->id}/licenses/{$index}")
            ];

        })
        ->values()
);
}
public function downloadLicense(User $pilot, $index)
{
    $profile = $pilot->pilotProfile;

    if (!$profile) {
        abort(404);
    }

    $files = $profile->licenses_attachments ?? [];

    if (!isset($files[$index])) {
        abort(404);
    }

    return Storage::disk('public')->download($files[$index]);
}
}