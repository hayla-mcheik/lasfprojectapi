<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EventController extends Controller
{
public function index(Request $request) 
{ 
    $query = Event::query();

    // Search filter
    if ($request->filled('search')) {
        $query->where('title', 'like', '%' . $request->search . '%');
    }

    // Status filter
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    return $query->latest()->get(); 
}
    public function store(Request $r)
    {
        $data = $r->validate([
            'title'=>'required',
            'start_date'=>'required|date',
            'end_date'=>'nullable|date',
            'description'=>'nullable',
            'image'=>'nullable|image'
        ]);



          // Handle image upload
        if ($r->hasFile('image')) {
            $path = $r->file('image')->store('events', 'public');
            $data['image'] = Storage::url($path);
        }
        $data['slug'] = Str::slug($data['title']);
        return Event::create($data);
    }

    public function update(Request $r, Event $event)
    {
        if ($r->hasFile('image')) {
        // Delete old image if exists
        if ($event->image) {
            // Extract filename from URL
            $oldImage = str_replace(Storage::url(''), '', $event->image);
            Storage::disk('public')->delete($oldImage);
        }
        
        $path = $r->file('image')->store('events', 'public');
        $data['image'] = Storage::url($path);
    } 
        $event->update($r->all());
        return $event;
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
