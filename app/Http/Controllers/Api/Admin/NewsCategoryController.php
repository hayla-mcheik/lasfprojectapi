<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\Request;

class NewsCategoryController extends Controller
{
    public function index()
    {
        return NewsCategory::latest()->get();
    }

 public function store(Request $request)
{
    $category = NewsCategory::create(
        $request->validate(['name' => 'required|string|max:255'])
    );

    return response()->json([
        'success' => true,
        'data' => $category,
        'message' => 'Category created successfully'
    ]);
}

public function update(Request $request, NewsCategory $newsCategory)
{
    $newsCategory->update(
        $request->validate(['name' => 'required|string|max:255'])
    );

    return response()->json([
        'success' => true,
        'data' => $newsCategory,
        'message' => 'Category updated successfully'
    ]);
}


    public function show(NewsCategory $newsCategory)
    {
        return $newsCategory;
    }



    public function destroy(NewsCategory $newsCategory)
    {
        $newsCategory->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
