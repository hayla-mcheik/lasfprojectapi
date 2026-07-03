<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PilotSafetyMessage;
class PilotSafetyMessageController extends Controller
{
        public function index()
    {
        return response()->json([
            'message' => PilotSafetyMessage::where('active', true)
                ->latest()
                ->first()
        ]);
    }
}
