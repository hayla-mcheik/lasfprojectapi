<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        // إضافة 'affectedLocations' لكي يتم إرسال البيانات إلى Nuxt
        $query = News::with(['categories', 'affectedLocations']);

        if ($request->has('category')) {
            $query->whereHas('categories', fn($q) => $q->where('name', $request->category));
        }

        $news = $query->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json($news);
    }

    public function show($slug)
    {
        // إضافة 'affectedLocations' هنا أيضاً لصفحة تفاصيل الخبر
        $news = News::with(['categories', 'affectedLocations'])
            ->where('slug', $slug)
            ->firstOrFail();
            
        return response()->json($news);
    }
}