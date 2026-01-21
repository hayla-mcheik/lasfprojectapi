<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;

class GalleryController extends Controller
{
public function index()
    {
        $allMedia = Gallery::latest()->get();

        // Group the data so Nuxt can find 'images' and 'videos'
        return response()->json([
            'images' => $allMedia->where('type', 'image')->values(),
            'videos' => $allMedia->where('type', 'video')->values()
        ]);
    }
}
