<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class WeatherApiService
{
    public function getLatestBulletin(): array
    {
        $url = rtrim(config('services.weather_api.url'), '/')
            . '/api/v1/bulletin/latest';

        $token = config('services.weather_api.token');

        if (empty($token)) {
            throw new Exception('Weather API token is not configured.');
        }

        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'X-Api-Token' => $token,
            ])
            ->get($url);

        if ($response->failed()) {
            throw new Exception(
                'Weather API failed: HTTP '
                . $response->status()
                . ' - '
                . $response->body()
            );
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new Exception('Weather API returned an invalid response.');
        }

        return $data;
    }
}