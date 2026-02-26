<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use App\Models\Regulation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PageContentController extends Controller
{
  public function updateAbout(Request $request)
{
    // 1. Validate the data FIRST
    $data = $request->validate([
        'title'   => 'required|string|max:255',
        'content' => 'required|string',
        'image'   => 'nullable|image|max:2048'
    ]);

    DB::beginTransaction();
    try {
        // 2. Find the first record or initialize a new one
        $about = AboutUs::first();

        // 3. Handle image upload if a new file is provided
        if ($request->hasFile('image')) {
            if ($about && $about->image) {
                // Delete old file from storage
                Storage::disk('public')->delete(str_replace('/storage/', '', $about->image));
            }
            $path = $request->file('image')->store('about', 'public');
            $data['image'] = Storage::url($path);
        }

        // 4. Use updateOrCreate to either update ID 1 or create it with the valid data
        $about = AboutUs::updateOrCreate(
            ['id' => 1], // The search criteria
            $data        // The data to insert or update
        );

        DB::commit();
        return response()->json(['success' => true, 'data' => $about]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
    // Regulations CRUD
    public function storeRegulation(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string',
            'title' => 'required|string',
            'content' => 'required|string',
            'is_critical' => 'boolean'
        ]);

        $reg = Regulation::create($data);
        return response()->json(['success' => true, 'data' => $reg]);
    }
    // Update an existing regulation
    public function updateRegulation(Request $request, $id)
    {
        $reg = Regulation::findOrFail($id);

        $data = $request->validate([
            'category' => 'sometimes|required|string',
            'title' => 'sometimes|required|string',
            'content' => 'sometimes|required|string',
            'is_critical' => 'boolean'
        ]);

        $reg->update($data);

        return response()->json([
            'success' => true, 
            'data' => $reg
        ]);
    }

    // Delete a regulation
    public function destroyRegulation($id)
    {
        $reg = Regulation::findOrFail($id);
        $reg->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Regulation deleted successfully'
        ]);
    }
}
