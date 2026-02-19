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
        
        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }
        
        if ($request->has('search') && $request->search != '') {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
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
        
        return $query->paginate(12); 
    }

    public function store(Request $request)
    {
        // Update validation to check for file type and a 20MB max size (20480 KB)
        $validator = Validator::make($request->all(), [
            'type'  => 'required|in:image,video',
            'title' => 'nullable|string|max:255',
            'file'  => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The uploaded file is too large or invalid.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('file')) {
            $folder = ($data['type'] === 'image') ? 'gallery/images' : 'gallery/videos';
            $path = $request->file('file')->store($folder, 'public');
            $data['file'] = Storage::url($path);
        }

        $gallery = Gallery::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully',
            'data'    => $gallery
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
                'errors'  => $validator->errors()
            ], 422);
        }

        $gallery->update($request->only('title'));

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully',
            'data'    => $gallery
        ]);
    }

    public function destroy(Gallery $gallery)
    {
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