<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $query = Gallery::query();
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        // Sort
        $sort = $request->sort ?? 'newest';
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        $gallery = $query->get();
        
        return response()->json($gallery);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:image,video',
            'title' => 'nullable|string|max:255',
            'file' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle file upload
        if ($request->hasFile('file')) {
            if ($data['type'] === 'image') {
                $path = $request->file('file')->store('gallery/images', 'public');
                $data['file'] = Storage::url($path);
            } else {
                $path = $request->file('file')->store('gallery/videos', 'public');
                $data['file'] = Storage::url($path);
            }
        } elseif ($request->has('file') && is_string($request->file)) {
            // If file is already a URL (for direct URL uploads)
            $data['file'] = $request->file;
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No valid file provided'
            ], 422);
        }

        $gallery = Gallery::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully',
            'data' => $gallery
        ]);
    }

    public function update(Request $request, Gallery $gallery)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $gallery->update($request->only('title'));

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully',
            'data' => $gallery
        ]);
    }

    public function destroy(Gallery $gallery)
    {
        // Delete file from storage
        if ($gallery->file) {
            $filePath = str_replace('/storage/', '', $gallery->file);
            Storage::disk('public')->delete($filePath);
        }

        $gallery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully'
        ]);
    }
}