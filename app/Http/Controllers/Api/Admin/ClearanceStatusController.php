<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClearanceStatus;
use App\Models\ClearanceStatusHistory;
use App\Models\FlyingLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClearanceStatusController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Get Daily Permissions
    |--------------------------------------------------------------------------
    |
    | GET:
    |
    | /admin/clearance-statuses?date=2026-07-14
    |
    | Returns EVERY enabled flying location.
    |
    | If no permission exists:
    |
    | status = red
    | has_permission = false
    |
    */

    public function index(Request $request)
    {
        $data = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'flying_location_id' =>
                'nullable|integer|exists:flying_locations,id',
        ]);

        $date = $data['date'] ?? today()->toDateString();

        $locations = FlyingLocation::query()
            ->where('is_enabled', true)

            ->when(
                isset($data['flying_location_id']),
                fn ($query) => $query->where(
                    'id',
                    $data['flying_location_id']
                )
            )

            ->with([
                'clearanceStatuses' => function ($query) use ($date) {
                    $query
                        ->whereDate('permission_date', $date)
                        ->with('updatedBy:id,name,email');
                },
            ])

            ->orderBy('name')

            ->get()

            ->map(function ($location) use ($date) {

                $permission =
                    $location->clearanceStatuses->first();

                return [
                    'flying_location_id' =>
                        $location->id,

                    'name' =>
                        $location->name,

                    'slug' =>
                        $location->slug,

                    'permission_date' =>
                        $date,

                    /*
                    |--------------------------------------------------------------------------
                    | No Request = CLOSED
                    |--------------------------------------------------------------------------
                    */

                    'status' =>
                        $permission?->status ?? 'red',

                    'reason' =>
                        $permission?->reason,

                    'has_permission' =>
                        $permission !== null,

                    'clearance_status_id' =>
                        $permission?->id,

                    'updated_by' =>
                        $permission?->updatedBy,

                    'updated_at' =>
                        $permission?->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $locations,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Daily Permission
    |--------------------------------------------------------------------------
    |
    | POST /admin/clearance-statuses
    |
    | If record already exists, update it.
    |
    */

    public function store(Request $request)
    {
        $data = $request->validate([
            'flying_location_id' =>
                'required|integer|exists:flying_locations,id',

            'permission_date' =>
                'required|date_format:Y-m-d',

            'status' =>
                'required|in:green,yellow,red',

            'reason' =>
                'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Lock Existing Daily Permission
            |--------------------------------------------------------------------------
            */

            $permission = ClearanceStatus::query()
                ->where(
                    'flying_location_id',
                    $data['flying_location_id']
                )
                ->whereDate(
                    'permission_date',
                    $data['permission_date']
                )
                ->lockForUpdate()
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Existing Permission
            |--------------------------------------------------------------------------
            */

            if ($permission) {

                $oldStatus = $permission->status;
                $oldReason = $permission->reason;

                $permission->update([
                    'status' =>
                        $data['status'],

                    'reason' =>
                        $data['reason'] ?? null,

                    'updated_by' =>
                        auth()->id(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Only Create History When Something Changed
                |--------------------------------------------------------------------------
                */

                if (
                    $oldStatus !== $permission->status
                    || $oldReason !== $permission->reason
                ) {
                    ClearanceStatusHistory::create([

                        'clearance_status_id' =>
                            $permission->id,

                        'flying_location_id' =>
                            $permission->flying_location_id,

                        'permission_date' =>
                            $permission->permission_date,

                        'old_status' =>
                            $oldStatus,

                        'old_reason' =>
                            $oldReason,

                        'new_status' =>
                            $permission->status,

                        'new_reason' =>
                            $permission->reason,

                        'changed_by' =>
                            auth()->id(),

                        'action' =>
                            'updated',
                    ]);
                }

                return response()->json([
                    'success' => true,

                    'message' =>
                        'Daily permission updated successfully.',

                    'data' =>
                        $permission->load([
                            'location',
                            'updatedBy',
                        ]),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create New Permission
            |--------------------------------------------------------------------------
            */

            $permission = ClearanceStatus::create([

                'flying_location_id' =>
                    $data['flying_location_id'],

                'permission_date' =>
                    $data['permission_date'],

                'status' =>
                    $data['status'],

                'reason' =>
                    $data['reason'] ?? null,

                'updated_by' =>
                    auth()->id(),
            ]);

            ClearanceStatusHistory::create([

                'clearance_status_id' =>
                    $permission->id,

                'flying_location_id' =>
                    $permission->flying_location_id,

                'permission_date' =>
                    $permission->permission_date,

                'old_status' =>
                    null,

                'old_reason' =>
                    null,

                'new_status' =>
                    $permission->status,

                'new_reason' =>
                    $permission->reason,

                'changed_by' =>
                    auth()->id(),

                'action' =>
                    'created',
            ]);

            return response()->json([
                'success' => true,

                'message' =>
                    'Daily permission created successfully.',

                'data' =>
                    $permission->load([
                        'location',
                        'updatedBy',
                    ]),
            ], 201);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Show Permission
    |--------------------------------------------------------------------------
    */

    public function show(ClearanceStatus $clearanceStatus)
    {
        return response()->json([
            'success' => true,

            'data' =>
                $clearanceStatus->load([
                    'location',
                    'updatedBy',
                    'histories.changedBy',
                ]),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Existing Permission
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        ClearanceStatus $clearanceStatus
    ) {
        $data = $request->validate([

            'status' =>
                'sometimes|required|in:green,yellow,red',

            'reason' =>
                'nullable|string|max:500',
        ]);

        return DB::transaction(
            function () use (
                $data,
                $clearanceStatus
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock Current Record
                |--------------------------------------------------------------------------
                */

                $permission = ClearanceStatus::query()
                    ->whereKey($clearanceStatus->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $oldStatus =
                    $permission->status;

                $oldReason =
                    $permission->reason;

                $permission->update([

                    'status' =>
                        $data['status']
                        ?? $permission->status,

                    'reason' =>
                        array_key_exists('reason', $data)
                            ? $data['reason']
                            : $permission->reason,

                    'updated_by' =>
                        auth()->id(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Write History Only If Changed
                |--------------------------------------------------------------------------
                */

                if (
                    $oldStatus !== $permission->status
                    || $oldReason !== $permission->reason
                ) {
                    ClearanceStatusHistory::create([

                        'clearance_status_id' =>
                            $permission->id,

                        'flying_location_id' =>
                            $permission->flying_location_id,

                        'permission_date' =>
                            $permission->permission_date,

                        'old_status' =>
                            $oldStatus,

                        'old_reason' =>
                            $oldReason,

                        'new_status' =>
                            $permission->status,

                        'new_reason' =>
                            $permission->reason,

                        'changed_by' =>
                            auth()->id(),

                        'action' =>
                            'updated',
                    ]);
                }

                return response()->json([
                    'success' => true,

                    'message' =>
                        'Daily permission updated successfully.',

                    'data' =>
                        $permission->load([
                            'location',
                            'updatedBy',
                        ]),
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Permission
    |--------------------------------------------------------------------------
    |
    | Deleting means there is no request for the date.
    |
    | Therefore public status becomes CLOSED.
    |
    */

    public function destroy(
        ClearanceStatus $clearanceStatus
    ) {
        return DB::transaction(
            function () use ($clearanceStatus) {

                $permission = ClearanceStatus::query()
                    ->whereKey($clearanceStatus->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                ClearanceStatusHistory::create([

                    /*
                    |--------------------------------------------------------------------------
                    | NULL because FK uses nullOnDelete()
                    |--------------------------------------------------------------------------
                    */

                    'clearance_status_id' =>
                        null,

                    'flying_location_id' =>
                        $permission->flying_location_id,

                    'permission_date' =>
                        $permission->permission_date,

                    'old_status' =>
                        $permission->status,

                    'old_reason' =>
                        $permission->reason,

                    'new_status' =>
                        null,

                    'new_reason' =>
                        null,

                    'changed_by' =>
                        auth()->id(),

                    'action' =>
                        'deleted',
                ]);

                $permission->delete();

                return response()->json([
                    'success' => true,

                    'message' =>
                        'Daily permission deleted. Site defaults to closed.',
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Permission History
    |--------------------------------------------------------------------------
    |
    | GET:
    |
    | /admin/clearance-statuses/history
    |
    | Optional:
    |
    | ?date=2026-07-14
    |
    | ?flying_location_id=5
    |
    */

    public function history(Request $request)
    {
        $data = $request->validate([

            'date' =>
                'nullable|date_format:Y-m-d',

            'flying_location_id' =>
                'nullable|integer|exists:flying_locations,id',
        ]);

        $history = ClearanceStatusHistory::query()

            ->with([
                'location:id,name,slug',
                'changedBy:id,name,email',
            ])

            ->when(
                isset($data['date']),

                fn ($query) =>
                    $query->whereDate(
                        'permission_date',
                        $data['date']
                    )
            )

            ->when(
                isset($data['flying_location_id']),

                fn ($query) =>
                    $query->where(
                        'flying_location_id',
                        $data['flying_location_id']
                    )
            )

            ->latest()

            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}