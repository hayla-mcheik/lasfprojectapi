<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::where('is_published', true)->get();
        return response()->json($pages);
    }

    public function show($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        return response()->json($page);
    }
}
