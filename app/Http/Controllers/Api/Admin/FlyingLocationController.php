<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlyingLocation;
use App\Models\QRCode;
use App\Models\ClearanceStatus;
use App\Models\ClearanceStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class FlyingLocationController extends Controller
{
public function index(Request $request)
{
$date = $request->input('date', today()->toDateString());

$query = FlyingLocation::with([

    'sports',

    'qrCode',

'clearanceStatuses' => function ($query) use ($date) {

    $query
        ->whereDate('permission_date', $date)
        ->orderByDesc('permission_date')
        ->with('updatedBy');

},

])->withCount([
    'activeSessions as active_sessions'
]);

    if ($request->filled('search')) {

        $search = $request->search;

        $query->where(function ($q) use ($search) {

            $q->where('name', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('takeoff_kato', 'like', "%{$search}%")
                ->orWhere('takeoff_nazim', 'like', "%{$search}%");

        });

    }

    if ($request->filled('enabled')) {

        $query->where(
            'is_enabled',
            filter_var(
                $request->enabled,
                FILTER_VALIDATE_BOOLEAN
            )
        );

    }

    $locations = $query
        ->orderBy('name')
        ->paginate($request->per_page ?? 20);

    return response()->json($locations);
}

public function store(Request $request)
{
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'type' => 'nullable|string',
    'takeoff_kato' => 'nullable|string',
    'takeoff_nazim' => 'nullable|string',
    'landing_kato' => 'nullable|string',
    'landing_nazim' => 'nullable|string',
    'boundaries_kato' => 'nullable|array',
    'boundaries_nazim' => 'nullable|array',
    'max_altitude' => 'nullable|string',
    'description' => 'nullable|string',
    'sports' => 'nullable|array',
    'sports.*' => 'exists:sports,id',
    'is_enabled' => 'boolean',
]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $data = $validator->validated();

    $data['slug'] = Str::slug($data['name']);
    $data['is_enabled'] = $data['is_enabled'] ?? true;

    DB::beginTransaction();

    try {

        $location = FlyingLocation::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'type' => $data['type'] ?? null,
            'takeoff_kato' => $data['takeoff_kato'] ?? null,
            'takeoff_nazim' => $data['takeoff_nazim'] ?? null,
            'landing_kato' => $data['landing_kato'] ?? null,
            'landing_nazim' => $data['landing_nazim'] ?? null,
            'boundaries_kato' => $data['boundaries_kato'] ?? null,
            'boundaries_nazim' => $data['boundaries_nazim'] ?? null,
            'max_altitude' => $data['max_altitude'] ?? null,
            'is_enabled' => $data['is_enabled'],
        ]);

        if (!empty($data['sports'])) {
            $location->sports()->sync($data['sports']);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'data' => $location->load([
                'sports',
                'qrCode',
                'clearanceStatuses' => function ($query) {
                    $query->whereDate('permission_date', today());
                },
            ]),
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);

    }
}
public function show(Request $request, FlyingLocation $flyingLocation)
{
    $date = $request->input('date', today()->toDateString());

    $flyingLocation->load([

        'sports',

        'qrCode',

        'clearanceStatuses' => function ($query) use ($date) {

            $query
                ->whereDate('permission_date', $date)
                ->with('updatedBy')
                ->latest();

        },

        'airspaceSessions' => function ($query) {

            $query
                ->where('status', 'active')
                ->whereNull('checked_out_at')
                ->where('expires_at', '>', now())
                ->with('pilot')
               ->orderByDesc('permission_date');

        },

    ]);

    return response()->json([
        'success' => true,
        'data' => $flyingLocation,
    ]);
}

public function update(Request $request, FlyingLocation $flyingLocation)
{
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'type' => 'nullable|string',
    'takeoff_kato' => 'nullable|string',
    'takeoff_nazim' => 'nullable|string',
    'landing_kato' => 'nullable|string',
    'landing_nazim' => 'nullable|string',
    'boundaries_kato' => 'nullable|array',
    'boundaries_nazim' => 'nullable|array',
    'max_altitude' => 'nullable|string',
    'description' => 'nullable|string',
    'sports' => 'nullable|array',
    'sports.*' => 'exists:sports,id',
    'is_enabled' => 'boolean',
]);
    if ($validator->fails()) {

        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);

    }

    $data = $validator->validated();

    if (isset($data['name'])) {
        $data['slug'] = Str::slug($data['name']);
    }

    DB::beginTransaction();

    try {

        $flyingLocation->update($data);

        if (isset($data['sports'])) {
            $flyingLocation->sports()->sync($data['sports']);
        }

        DB::commit();

        $date = $request->input('date', today()->toDateString());

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $flyingLocation->load([

                'sports',

                'qrCode',

                'clearanceStatuses' => function ($query) use ($date) {

                    $query
                        ->whereDate('permission_date', $date)
                        ->with('updatedBy')
                        ->latest();

                },

            ])
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);

    }
}
    public function destroy(FlyingLocation $flyingLocation)
    {
        DB::beginTransaction();
        try {
            $flyingLocation->clearanceStatuses()->delete();
            $flyingLocation->airspaceSessions()->delete();
            
            if ($flyingLocation->qrCode) {
                $flyingLocation->qrCode()->delete();
            }
            
            $flyingLocation->sports()->detach();
            $flyingLocation->delete();

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function regions()
    {
        // Brief update: Excel has no regions, so we use Type or unique Name fragments
        $types = FlyingLocation::distinct()
            ->whereNotNull('type')
            ->orderBy('type')
            ->pluck('type');

        return response()->json([
            'success' => true,
            'data' => $types
        ]);
    }

    public function generateQR(FlyingLocation $flyingLocation)
    {
        if ($flyingLocation->qrCode) {
            return response()->json([
                'success' => true,
                'message' => 'QR code already exists',
                'data' => $flyingLocation->qrCode
            ]);
        }

        DB::beginTransaction();
        try {
            $token = Uuid::uuid4()->toString();
            
            $qrCode = QRCode::create([
                'flying_location_id' => $flyingLocation->id,
                'token' => $token
            ]);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'QR code generated successfully',
                'data' => $qrCode
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getQRCodes(FlyingLocation $flyingLocation)
    {
        $qrCodes = $flyingLocation->qrCode()->get();
        
        return response()->json([
            'success' => true,
            'data' => $qrCodes
        ]);
    }

public function statistics(Request $request)
{
    $date = $request->input(
        'date',
        today()->toDateString()
    );

    $totalLocations = FlyingLocation::count();

    $enabledLocations = FlyingLocation::where(
        'is_enabled',
        true
    )->count();

    $locationsWithQR = FlyingLocation::has('qrCode')->count();

    $statusDistribution = DB::table('clearance_statuses')
        ->select(
            'status',
            DB::raw('COUNT(*) as count')
        )
        ->whereDate(
            'permission_date',
            $date
        )
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();

    return response()->json([

        'success' => true,

        'date' => $date,

        'data' => [

            'total_locations' => $totalLocations,

            'enabled_locations' => $enabledLocations,

            'locations_with_qr' => $locationsWithQR,

            'status_distribution' => [

                'green' => $statusDistribution['green'] ?? 0,

                'yellow' => $statusDistribution['yellow'] ?? 0,

                'red' => $statusDistribution['red'] ?? 0,

            ],

        ],

    ]);
}
}