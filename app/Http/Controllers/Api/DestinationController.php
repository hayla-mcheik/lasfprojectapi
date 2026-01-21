<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Destination;

class DestinationController extends Controller
{
    public function index()
    {
        return Destination::latest()->get();
    }

    public function show($slug)
    {
        return Destination::where('slug', $slug)->firstOrFail();
    }
}
