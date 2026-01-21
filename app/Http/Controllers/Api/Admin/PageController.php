<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index()
    {
        return Page::latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $data['slug'] = Str::slug($data['title']);

        return Page::create($data);
    }

    public function show(Page $page)
    {
        return $page;
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        $page->update($data);

        return $page;
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return response()->json(['message' => 'Page deleted']);
    }
}
