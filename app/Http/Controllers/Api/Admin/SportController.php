<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SportController extends Controller
{
    public function index(Request $request)
    {
        $sports = Sport::withCount('flyingLocations')
            ->orderBy('name')
            ->get();
        
        return response()->json($sports);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sports,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('sports', 'public');
            $data['image'] = Storage::url($path);
        }

        $sport = Sport::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Sport created successfully',
            'data' => $sport
        ]);
    }

    public function show(Sport $sport)
    {
        $sport->loadCount('flyingLocations');
        
        return response()->json([
            'success' => true,
            'data' => $sport
        ]);
    }

// app/Http/Controllers/Api/Admin/SportController.php

public function update(Request $request, Sport $sport)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255|unique:sports,name,' . $sport->id,
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    $data = $validator->validated();

    if ($request->hasFile('image')) {
        // Delete old image if exists
        if ($sport->image) {
            // Extract filename from URL
            $oldImage = str_replace(Storage::url(''), '', $sport->image);
            Storage::disk('public')->delete($oldImage);
        }
        
        $path = $request->file('image')->store('sports', 'public');
        $data['image'] = Storage::url($path);
    } 
    // Handle removal of image if explicitly requested
    elseif ($request->remove_image === 'true') {
        if ($sport->image) {
            $oldImage = str_replace(Storage::url(''), '', $sport->image);
            Storage::disk('public')->delete($oldImage);
        }
        $data['image'] = null;
    }

    $sport->update($data);

    return response()->json([
        'success' => true,
        'message' => 'Sport updated successfully',
        'data' => $sport->loadCount('flyingLocations')
    ]);
}

    public function destroy(Sport $sport)
    {
        // Delete image if exists
        if ($sport->image) {
            $oldImage = str_replace('/storage/', '', $sport->image);
            Storage::disk('public')->delete($oldImage);
        }

        $sport->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sport deleted successfully'
        ]);
    }
}