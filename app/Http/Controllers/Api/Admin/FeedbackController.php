<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackReport;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * List all reports
     */
    public function index()
    {
        return FeedbackReport::with('location')
            ->latest()
            ->paginate(15);
    }

    /**
     * View one report
     */
    public function show(FeedbackReport $feedback)
    {
        return $feedback->load('location');
    }

    /**
     * Update report status / notes
     */
    public function update(Request $request, FeedbackReport $feedback)
    {
        $request->validate([
            'status' => 'required|in:new,in_progress,closed',
            'admin_notes' => 'nullable|string',
        ]);

        $feedback->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'message' => 'Feedback updated successfully.',
            'feedback' => $feedback,
        ]);
    }
}