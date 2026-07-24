<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlyingLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlyingLocationController extends Controller
{
    /**
     * Public flying locations.
     */
    public function index(Request $request): JsonResponse
    {
        $date = $request->input(
            'date',
            today()->toDateString()
        );

        $locations = FlyingLocation::query()
            ->where('is_enabled', true)
            ->with([

                'sports',

                'qrCode',

                'clearanceStatuses' => function ($query) use ($date) {

                    $query
                        ->whereDate('permission_date', $date)
                        ->with('updatedBy')
                        ->latest();

                },

            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $locations,
        ]);
    }

    /**
     * Single flying location.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $date = $request->input(
            'date',
            today()->toDateString()
        );

        $location = FlyingLocation::query()
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->with([

                'sports',

                'qrCode',

                'clearanceStatuses' => function ($query) use ($date) {

                    $query
                        ->whereDate('permission_date', $date)
                        ->with('updatedBy')
                        ->latest();

                },

            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'date' => $date,
            'data' => $location,
        ]);
    }
}