<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MicrosoftGraphService
{
    public static function getAccessToken()
    {
        $clientId = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');
        $tenantId = config('services.microsoft.tenant_id');

        $url = "https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token";

        $response = Http::asForm()->post($url, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to retrieve access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }
}
