<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;

class TestimonialController extends Controller
{
    public function index()
    {
        return Testimonial::latest()->get();
    }
}
