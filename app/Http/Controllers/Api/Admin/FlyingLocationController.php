<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlyingLocation;
use App\Models\QRCode;
use App\Models\ClearanceStatus;
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
        $query = FlyingLocation::with(['sports', 'clearanceStatuses' => function($q) {
            $q->latest()->limit(1);
        }, 'qrCode'])
            ->withCount(['airspaceSessions as active_sessions' => function($q) {
                $q->where('status', 'active')
                  ->whereNull('checked_out_at')
                  ->where('expires_at', '>', now());
            }]);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('region', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter (based on clearance status)
        if ($request->has('status') && $request->status) {
            $status = $request->status;
            $query->whereHas('clearanceStatuses', function($q) use ($status) {
                $q->where('status', $status)
                  ->whereIn('id', function($subquery) {
                      $subquery->select(DB::raw('MAX(id)'))
                              ->from('clearance_statuses')
                              ->groupBy('flying_location_id');
                  });
            });
        }

        // Region filter
        if ($request->has('region') && $request->region) {
            $query->where('region', $request->region);
        }

        // Enabled filter
        if ($request->has('enabled')) {
            $query->where('is_enabled', filter_var($request->enabled, FILTER_VALIDATE_BOOLEAN));
        }

        // Pagination
        $perPage = $request->per_page ?? 20;
        $locations = $query->orderBy('name')->paginate($perPage);

        return response()->json($locations);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'region' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
            'sports' => 'nullable|array',
            'sports.*' => 'exists:sports,id',
            'is_enabled' => 'boolean',
            'clearance_status' => 'nullable|in:green,red',
            'clearance_reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['name']);
        $data['is_enabled'] = $data['is_enabled'] ?? true;

        DB::beginTransaction();
        try {
            $location = FlyingLocation::create($data);

            // Attach sports
            if (isset($data['sports']) && is_array($data['sports'])) {
                $location->sports()->sync($data['sports']);
            }

            // Create initial clearance status if provided
            if (isset($data['clearance_status'])) {
                $location->clearanceStatuses()->create([
                    'status' => $data['clearance_status'],
                    'reason' => $data['clearance_reason'] ?? null,
                    'updated_by' => auth()->id()
                ]);
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Location created successfully',
                'data' => $location->load(['sports', 'clearanceStatuses', 'qrCode'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(FlyingLocation $flyingLocation)
    {
        $flyingLocation->load(['sports', 'clearanceStatuses', 'qrCode', 
            'airspaceSessions' => function($q) {
                $q->where('status', 'active')
                  ->whereNull('checked_out_at')
                  ->with('pilot')
                  ->latest();
            }]);
        
        return response()->json([
            'success' => true,
            'data' => $flyingLocation
        ]);
    }

    public function update(Request $request, FlyingLocation $flyingLocation)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'region' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string',
            'sports' => 'nullable|array',
            'sports.*' => 'exists:sports,id',
            'is_enabled' => 'boolean',
            'clearance_status' => 'nullable|in:green,red',
            'clearance_reason' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
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

            // Update sports
            if (isset($data['sports'])) {
                $flyingLocation->sports()->sync($data['sports']);
            }

            // Update clearance status if changed
            if (isset($data['clearance_status'])) {
                $latestStatus = $flyingLocation->clearanceStatuses()->latest()->first();
                
                if (!$latestStatus || $latestStatus->status !== $data['clearance_status']) {
                    $flyingLocation->clearanceStatuses()->create([
                        'status' => $data['clearance_status'],
                        'reason' => $data['clearance_reason'] ?? null,
                        'updated_by' => auth()->id()
                    ]);
                } elseif ($latestStatus && $latestStatus->reason !== ($data['clearance_reason'] ?? null)) {
                    $latestStatus->update([
                        'reason' => $data['clearance_reason'] ?? null
                    ]);
                }
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'data' => $flyingLocation->load(['sports', 'clearanceStatuses'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(FlyingLocation $flyingLocation)
    {
        DB::beginTransaction();
        try {
            // Delete related records
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

    /**
     * Get distinct regions from flying locations
     */
    public function regions()
    {
        $regions = FlyingLocation::distinct()
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->orderBy('region')
            ->pluck('region');

        return response()->json([
            'success' => true,
            'data' => $regions
        ]);
    }

    /**
     * Generate QR code for a flying location
     */
    public function generateQR(FlyingLocation $flyingLocation)
    {
        // Check if QR code already exists
        if ($flyingLocation->qrCode) {
            return response()->json([
                'success' => true,
                'message' => 'QR code already exists',
                'data' => $flyingLocation->qrCode
            ]);
        }

        DB::beginTransaction();
        try {
            // Generate unique token
            $token = Uuid::uuid4()->toString();
            
            // Create QR code record
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

    /**
     * Get QR codes for a location
     */
    public function getQRCodes(FlyingLocation $flyingLocation)
    {
        $qrCodes = $flyingLocation->qrCode()->get();
        
        return response()->json([
            'success' => true,
            'data' => $qrCodes
        ]);
    }

    /**
     * Get location statistics
     */
    public function statistics()
    {
        $totalLocations = FlyingLocation::count();
        $enabledLocations = FlyingLocation::where('is_enabled', true)->count();
        $locationsWithQR = FlyingLocation::has('qrCode')->count();
        
        // Get status distribution
        $statusDistribution = DB::table('clearance_statuses')
            ->select('status', DB::raw('COUNT(DISTINCT flying_location_id) as count'))
            ->whereIn('id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('clearance_statuses')
                    ->groupBy('flying_location_id');
            })
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'total_locations' => $totalLocations,
                'enabled_locations' => $enabledLocations,
                'locations_with_qr' => $locationsWithQR,
                'status_distribution' => $statusDistribution
            ]
        ]);
    }
}