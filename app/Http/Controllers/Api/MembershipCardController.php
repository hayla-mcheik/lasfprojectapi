<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MembershipCardController extends Controller
{
  public function download(Request $request)
{
    try {

        $user = $request->user();

        $user->load([
            'pilotProfile',
            'pilotProfile.disciplines',
        ]);

        if (!$user->pilotProfile) {
            return response()->json([
                'message' => 'Membership not found.'
            ], 404);
        }

        $profile = $user->pilotProfile;

        $license = $profile->license_number;

        $qrCode = $request->input('qrCode');

        if (
            !empty($profile->image) &&
            file_exists(public_path($profile->image))
        ) {
            $photoPath = public_path($profile->image);
        } else {
            $photoPath = public_path('assets/images/avatarpilot.jpg');
        }

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(
            'pdf.membership-card',
            [
                'user' => $user,
                'profile' => $profile,
                'photoPath' => $photoPath,
                'qrCode' => $qrCode,
            ]
        );

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download(
            'membership-card-' . $license . '.pdf'
        );

    } catch (\Exception $e) {

        return response()->json([
            'message' => $e->getMessage(),
        ], 500);
    }
}
}