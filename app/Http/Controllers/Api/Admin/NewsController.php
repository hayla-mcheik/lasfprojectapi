<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = News::with(['categories', 'creator','affectedLocations'])
            ->orderBy('created_at', 'desc');

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        // Status filter
        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        // Category filter
        if ($request->has('category')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('id', $request->category);
            });
        }

        // Pagination
        $perPage = $request->per_page ?? 15;
        $news = $query->paginate($perPage);

        return response()->json($news);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:news_categories,id',
            'affected_locations' => 'nullable|array', // Add this
        'affected_locations.*' => 'exists:flying_locations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['slug'] = Str::slug($data['title']);
        $data['created_by'] = auth()->id();

        // Set published_at if publishing
        if (($data['is_published'] ?? false) && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        DB::beginTransaction();
        try {
            $news = News::create($data);

            // Attach categories
            if (isset($data['categories'])) {
                $news->categories()->sync($data['categories']);
            }
            // Sync affected locations
        if (isset($request->affected_locations)) {
            $news->affectedLocations()->sync($request->affected_locations);
        }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'News article created successfully',
                'data' => $news->load(['categories', 'creator','affectedLocations'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create news article',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(News $news)
    {
        return response()->json([
            'success' => true,
            'data' => $news->load(['categories', 'creator'])
        ]);
    }

    public function update(Request $request, News $news)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:news_categories,id',
                        'affected_locations' => 'nullable|array', // Add this
        'affected_locations.*' => 'exists:flying_locations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Set published_at if publishing for the first time
        if (($data['is_published'] ?? false) && !$news->is_published && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        DB::beginTransaction();
        try {
            $news->update($data);

            // Sync categories
            if (isset($data['categories'])) {
                $news->categories()->sync($data['categories']);
            }
                        // Sync affected locations
        if (isset($request->affected_locations)) {
            $news->affectedLocations()->sync($request->affected_locations);
        }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'News article updated successfully',
                'data' => $news->load(['categories', 'creator','affectedLocations'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update news article',
                'error' => $e->getMessage()
            ], 500);
        }
    }

 public function destroy($id) // غير News $news إلى $id للتجربة
{
    $news = News::findOrFail($id);
    DB::beginTransaction();
    try {
        $news->categories()->detach();
        $news->affectedLocations()->detach();
        $news->delete();
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    /**
     * Toggle publish status
     */
    public function togglePublish(News $news)
    {
        DB::beginTransaction();
        try {
            $news->is_published = !$news->is_published;
            
            if ($news->is_published && !$news->published_at) {
                $news->published_at = now();
            }
            
            $news->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $news->is_published ? 'News article published' : 'News article unpublished',
                'data' => $news
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle publish status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}