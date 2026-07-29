<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMail;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Save in database
        $contact = ContactMessage::create($validated);

        // Send email
        Mail::to('contact@lasf.info')->send(
            new ContactMail($contact)
        );

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.'
        ]);
    }
}