<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CrossCountryRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
class CrossCountryCardController extends Controller
{


public function generate($id)
{
    $request = CrossCountryRequest::with([
        'pilot.pilotProfile',
        'locations.location',
        'qrCode',
    ])->findOrFail($id);

    /*
    |--------------------------------------------------------------------------
    | Generate QR Image
    |--------------------------------------------------------------------------
    */

    $qrUrl = url('/cross-country/' . $request->qrCode->token);

    $qrImage = base64_encode(
        QrCode::format('png')
            ->size(250)
            ->margin(1)
            ->generate($qrUrl)
    );

    /*
    |--------------------------------------------------------------------------
    | Generate PDF
    |--------------------------------------------------------------------------
    */

    $pdf = Pdf::loadView('pdf.cross-country-card', [
        'request' => $request,
        'qrImage' => $qrImage,
    ]);

    return $pdf->download('cross-country-card.pdf');
}
}
