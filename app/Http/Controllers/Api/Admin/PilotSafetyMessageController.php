<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PilotSafetyMessage;
class PilotSafetyMessageController extends Controller
{
        public function index()
    {
        return response()->json([
            'message' => PilotSafetyMessage::latest()->first()
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $message = PilotSafetyMessage::latest()->first();

        if (!$message) {

            $message = PilotSafetyMessage::create([
                'title' => $request->title,
                'message' => $request->message,
                'active' => true,
            ]);

        } else {

            $message->update([
                'title' => $request->title,
                'message' => $request->message,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
