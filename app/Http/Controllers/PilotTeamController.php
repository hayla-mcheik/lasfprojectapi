<?php

namespace App\Http\Controllers;

use App\Models\PilotProfile;
use Illuminate\Http\Request;

class PilotTeamController extends Controller
{
       public function index()
    {
return PilotProfile::with('user')->get();
    }
}
