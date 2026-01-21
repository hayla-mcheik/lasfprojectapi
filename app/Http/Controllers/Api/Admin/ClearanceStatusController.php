<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClearanceStatus;
use Illuminate\Http\Request;

class ClearanceStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = ClearanceStatus::with(['location', 'updatedBy']);

        // Filter by location if provided
        if ($request->has('flying_location_id')) {
            $query->where('flying_location_id', $request->flying_location_id);
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'flying_location_id' => 'required|exists:flying_locations,id',
       'status' => 'required|in:green,red',
            'reason' => 'nullable|string|max:500'
        ]);

        $data['updated_by'] = auth()->id();

        return ClearanceStatus::create($data);
    }

    public function show(ClearanceStatus $clearanceStatus)
    {
        return $clearanceStatus->load(['location', 'updatedBy']);
    }

    public function update(Request $request, ClearanceStatus $clearanceStatus)
    {
        $data = $request->validate([
  'status' => 'spmetimes|in:green,red',
            'reason' => 'nullable|string|max:500'
        ]);

        $data['updated_by'] = auth()->id();

        $clearanceStatus->update($data);

        return $clearanceStatus->load(['location', 'updatedBy']);
    }

    public function destroy(ClearanceStatus $clearanceStatus)
    {
        $clearanceStatus->delete();

        return response()->json(['message' => 'Clearance status deleted']);
    }
}