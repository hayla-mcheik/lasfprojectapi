<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;

class ContactMessageController extends Controller
{
    public function index()
    {
        return response()->json(
            ContactMessage::latest()->paginate(15)
        );
    }

public function show(ContactMessage $contactMessage)
{
    $contactMessage->update([
        'is_read' => true
    ]);

    return response()->json($contactMessage);
}
    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return response()->json([
            'message' => 'Message deleted successfully.'
        ]);
    }
}