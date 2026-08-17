<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrossCountryRequest;
use App\Models\CrossCountryQRCode;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class CrossCountryQRCodeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Generate QR Code
    |--------------------------------------------------------------------------
    */

    public function generateQR(CrossCountryRequest $request)
    {
        if ($request->qrCode) {

            return response()->json([
                'success' => true,
                'message' => 'QR Code already exists.',
                'data' => $request->qrCode,
            ]);

        }

        DB::beginTransaction();

        try {

            $qrCode = CrossCountryQRCode::create([

                'cross_country_request_id' => $request->id,

                'token' => Uuid::uuid4()->toString(),

            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'QR Code generated successfully.',
                'data' => $qrCode,
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);

        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get QR Code
    |--------------------------------------------------------------------------
    */

    public function getQRCode(CrossCountryRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->qrCode,
        ]);
    }
}