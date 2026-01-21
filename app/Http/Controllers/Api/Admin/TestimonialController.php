<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestimonialController extends Controller
{
    public function index()
    {
        return Testimonial::latest()->get();
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($r->hasFile('image')) {
            $data['image'] = $r->file('image')->store('testimonials', 'public');
        }

        return Testimonial::create($data);
    }

// TestimonialController.php

public function update(Request $r, Testimonial $testimonial)
{
    // Note: If sending via FormData spoofing, Laravel validates this correctly
    $data = $r->validate([
        'name' => 'required|string',
        'description' => 'required|string',
        'image' => 'nullable|image|max:2048', // Ensure this is nullable
    ]);

    if ($r->hasFile('image')) {
        // delete old image
        if ($testimonial->image) {
            Storage::disk('public')->delete($testimonial->image);
        }

        // Store new image and update the path in the data array
        $data['image'] = $r->file('image')->store('testimonials', 'public');
    }

    $testimonial->update($data);

    return response()->json([
        'success' => true,
        'message' => 'Updated successfully',
        'testimonial' => $testimonial
    ]);
}
    public function destroy(Testimonial $testimonial)
    {
        if ($testimonial->image) {
            Storage::disk('public')->delete($testimonial->image);
        }

        $testimonial->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
