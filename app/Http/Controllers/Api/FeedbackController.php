<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeedbackReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([

            'type' => 'required|in:feedback,complaint,safety,violation,other',

            'subject' => 'nullable|string|max:255',

            'message' => 'required|string|min:10',

            'flying_location_id' => 'nullable|exists:flying_locations,id',

            'incident_date' => 'nullable|date',

            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

        ]);

        $attachment = null;

        if ($request->hasFile('attachment')) {

            $attachment = $request
                ->file('attachment')
                ->store('feedback', 'public');

        }

        $feedback = FeedbackReport::create([

            'type' => $request->type,

            'subject' => $request->subject,

            'message' => $request->message,

            'flying_location_id' => $request->flying_location_id,

            'incident_date' => $request->incident_date,

            'attachment' => $attachment,

        ]);

        return response()->json([

            'message' => 'Thank you. Your report has been submitted anonymously.',

            'feedback' => $feedback,

        ], 201);
    }
}