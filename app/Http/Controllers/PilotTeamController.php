<?php

namespace App\Http\Controllers;

use App\Models\PilotProfile;
use Illuminate\Http\Request;

class PilotTeamController extends Controller
{
    /**
     * Public pilots listing
     */
    public function index()
    {
        return PilotProfile::with([
            'user',
            'disciplines'
        ])->get();
    }

    /**
     * Public pilot profile by license number
     */
    public function show($license)
    {
        $profile = PilotProfile::with([
            'user',
            'disciplines'
        ])
        ->where('license_number', $license)
        ->firstOrFail();

        return response()->json([
            'pilot' => $profile
        ]);
    }
}