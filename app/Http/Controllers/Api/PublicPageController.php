<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use App\Models\Regulation;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function getAbout()
    {
        return response()->json(AboutUs::first());
    }

    public function getRegulations()
    {
        return response()->json(
            Regulation::orderBy('order_priority', 'asc')->get()->groupBy('category')
        );
    }
}
