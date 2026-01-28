<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query();
        
        // Add search filter
        if ($request->has('search') && $request->search) {
            $query->where('title', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
        }
        
        // Add status filter
        if ($request->has('status') && $request->status) {
            $now = now();
            switch($request->status) {
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
                case 'ongoing':
                    $query->where('start_date', '<=', $now)
                          ->where(function($q) use ($now) {
                              $q->whereNull('end_date')
                                ->orWhere('end_date', '>=', $now);
                          });
                    break;
                case 'completed':
                    $query->where('end_date', '<', $now);
                    break;
            }
        }
        
        // Add type filter
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        
        // Pagination
        $perPage = $request->per_page ?? 10;
        $events = $query->latest()->paginate($perPage);
        
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:upcoming,ongoing,completed,cancelled',
            'registration_link' => 'nullable|url',
            'image' => 'nullable|image|max:2048',
            'sports' => 'nullable|array',
            'sports.*' => 'exists:sports,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Generate slug
        $data['slug'] = Str::slug($data['title']) . '-' . time();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('events', 'public');
            $data['image'] = Storage::url($path);
        }
        
        // Create event
        $event = Event::create($data);
        
        // Attach sports if provided
        if ($request->has('sports')) {
            $event->sports()->sync($request->sports);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Event created successfully',
            'data' => $event->load('sports')
        ], 201);
    }

    public function show(Event $event)
    {
        return response()->json([
            'success' => true,
            'data' => $event->load('sports')
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:upcoming,ongoing,completed,cancelled',
            'registration_link' => 'nullable|url',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable|boolean',
            'sports' => 'nullable|array',
            'sports.*' => 'exists:sports,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Update slug if title changed
        if ($request->has('title') && $request->title !== $event->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . time();
        }
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($event->image) {
                $oldImage = str_replace('/storage/', '', $event->image);
                Storage::disk('public')->delete($oldImage);
            }
            
            // Upload new image
            $path = $request->file('image')->store('events', 'public');
            $data['image'] = Storage::url($path);
        } elseif ($request->has('remove_image') && $request->remove_image) {
            // Remove image
            if ($event->image) {
                $oldImage = str_replace('/storage/', '', $event->image);
                Storage::disk('public')->delete($oldImage);
            }
            $data['image'] = null;
        }
        
        // Update event
        $event->update($data);
        
        // Sync sports if provided
        if ($request->has('sports')) {
            $event->sports()->sync($request->sports);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully',
            'data' => $event->fresh()->load('sports')
        ]);
    }

    public function destroy(Event $event)
    {
        try {
            // Delete image if exists
            if ($event->image) {
                $imagePath = str_replace('/storage/', '', $event->image);
                Storage::disk('public')->delete($imagePath);
            }
            
            // Detach sports
            $event->sports()->detach();
            
            // Delete event
            $event->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete event',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}