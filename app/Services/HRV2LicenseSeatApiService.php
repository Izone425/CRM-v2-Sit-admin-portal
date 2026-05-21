<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HRV2LicenseSeatApiService
{
    private string $apiUrl;
    private string $apiKey;
    private string $privateKeyPath;
    private $privateKey;

    public function __construct()
    {
        $this->apiUrl = config('services.crm.api_url') ?? 'https://profile-crm-hr-test.timeteccloud.com';
        $this->apiKey = config('services.crm.api_key') ?? 'crm_external_api';

        $configPath = config('services.crm.private_key_path', 'storage/keys/crm_client.private.pem');

        if (strpos($configPath, '/') !== 0) {
            $this->privateKeyPath = base_path($configPath);
        } else {
            $this->privateKeyPath = $configPath;
        }

        if (empty($this->apiUrl)) {
            throw new \Exception("CRM API URL is not configured");
        }

        if (empty($this->apiKey)) {
            throw new \Exception("CRM API Key is not configured");
        }

        $this->loadPrivateKey();
    }

    private function loadPrivateKey(): void
    {
        if (!file_exists($this->privateKeyPath)) {
            throw new \Exception("Private key not found at: {$this->privateKeyPath}");
        }

        $keyContent = file_get_contents($this->privateKeyPath);

        if (empty($keyContent)) {
            throw new \Exception("Private key file is empty");
        }

        $this->privateKey = openssl_pkey_get_private($keyContent);

        if (!$this->privateKey) {
            throw new \Exception("Failed to load private key: " . openssl_error_string());
        }

        Log::info("CRM API: Private key loaded successfully");
    }

    private function createSignature(string $payload, string $timestamp): string
    {
        $dataToSign = $payload . $timestamp;

        $signature = '';
        $success = openssl_sign(
            $dataToSign,
            $signature,
            $this->privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$success) {
            throw new \Exception("Failed to create signature: " . openssl_error_string());
        }

        return base64_encode($signature);
    }

    private function getTimestamp(): string
    {
        return gmdate('Y-m-d\TH:i:s.v\Z');
    }

    private function makeRequest(string $method, string $endpoint, ?array $data = null): array
    {
        $payload = $data ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $timestamp = $this->getTimestamp();
        $signature = $this->createSignature($payload, $timestamp);

        $url = $this->apiUrl . $endpoint;

        Log::info("CRM API Request Details", [
            'method' => $method,
            'url' => $url,
            'timestamp' => $timestamp,
            'payload' => $payload,
            'payload_length' => strlen($payload),
            'signature' => substr($signature, 0, 50) . '...',
        ]);

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->apiKey,
                'X-Signature' => $signature,
                'X-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
            ])
            ->withBody($payload, 'application/json')
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->send($method, $url);

            $statusCode = $response->status();
            $responseBody = $response->body();

            Log::info("CRM API Response", [
                'status' => $statusCode,
                'body' => $responseBody,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error("CRM API Error", [
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'body' => $responseBody,
            ]);

            return [
                'success' => false,
                'error' => $response->json()['error'] ?? $responseBody,
                'status' => $statusCode
            ];

        } catch (\Exception $e) {
            Log::error("CRM API Exception", [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function createAccount(array $data): array
    {
        $required = ['company_name', 'country_id', 'name', 'email', 'password', 'phone_code', 'phone', 'timezone'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return [
                    'success' => false,
                    'error' => "Missing required field: $field"
                ];
            }
        }

        if (!$this->isValidIANATimezone($data['timezone'])) {
            return [
                'success' => false,
                'error' => "Invalid timezone: {$data['timezone']}. Must be IANA format like 'Asia/Kuala_Lumpur'"
            ];
        }

        $payload = [
            'companyName' => $data['company_name'],
            'countryId' => (int)$data['country_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phoneCode' => $data['phone_code'],
            'phone' => $data['phone'],
            'timezone' => $data['timezone'],
        ];

        Log::info("CRM API: Creating account", $payload);

        return $this->makeRequest('POST', '/api/crm/account', $payload);
    }

    private function isValidIANATimezone(string $timezone): bool
    {
        $validTimezones = timezone_identifiers_list();
        return in_array($timezone, $validTimezones);
    }

    /**
     * ✅ FLEXIBLE: Add buffer license with ONLY seatLimits
     * - If seatLimits is empty/null → ALL modules with UNLIMITED seats
     * - If seatLimits has specific modules → ONLY those modules activated
     * - null seat value → unlimited seats for that module
     * - numeric seat value → limited seats for that module
     */
    public function addBufferLicense(int $accountId, int $companyId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer";

        // ✅ Prepare payload - only include what's provided
        $payload = [
            'startDate' => $licenseData['startDate'],
            'endDate' => $licenseData['endDate'],
            'notes' => $licenseData['notes'] ?? null,
        ];

        // ✅ Only add applications if explicitly provided
        if (isset($licenseData['applications']) && !empty($licenseData['applications'])) {
            $payload['applications'] = $licenseData['applications'];
        }

        // ✅ Only add seatLimits if explicitly provided
        if (isset($licenseData['seatLimits']) && !empty($licenseData['seatLimits'])) {
            $payload['seatLimits'] = $licenseData['seatLimits'];
        }

        Log::info("Adding buffer license", [
            'account_id' => $accountId,
            'company_id' => $companyId,
            'payload' => $payload,
            'has_applications' => isset($payload['applications']),
            'has_seat_limits' => isset($payload['seatLimits']),
        ]);

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * ✅ FLEXIBLE: Update buffer license with ONLY seatLimits
     */
    public function updateBufferLicense(int $accountId, int $companyId, int $licenseSetId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer/{$licenseSetId}";

        $payload = [
            'startDate' => $licenseData['startDate'],
            'endDate' => $licenseData['endDate'],
            'notes' => $licenseData['notes'] ?? null,
        ];

        if (isset($licenseData['seatLimits']) && !empty($licenseData['seatLimits'])) {
            $payload['seatLimits'] = $licenseData['seatLimits'];
        }

        Log::info("Updating buffer license (flexible)", [
            'account_id' => $accountId,
            'company_id' => $companyId,
            'license_set_id' => $licenseSetId,
            'payload' => $payload,
        ]);

        return $this->makeRequest('PUT', $endpoint, $payload);
    }

    /**
     * ✅ FLEXIBLE: Add paid application license
     * - application: module name (e.g., 'Attendance')
     * - seatLimit: null = unlimited, number = limited
     */
    public function addPaidApplicationLicense(int $accountId, int $companyId, array $licenseData): array
    {
        // ✅ Fix the endpoint - use actual companyId value, not placeholder
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/paid-app";

        // Prepare payload
        $payload = [
            'application' => $licenseData['application'],
            'startDate' => $licenseData['startDate'],
            'endDate' => $licenseData['endDate'],
            'seatLimit' => $licenseData['seatLimit'],
        ];

        // Add userId if provided
        if (isset($licenseData['userId'])) {
            $payload['userId'] = $licenseData['userId'];
        }

        Log::info("Adding paid application license (fixed endpoint)", [
            'account_id' => $accountId,
            'company_id' => $companyId, // ✅ Log the actual company ID
            'endpoint' => $endpoint, // ✅ Log the fixed endpoint
            'payload' => $payload
        ]);

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * ✅ FLEXIBLE: Update paid application license
     */
    public function updatePaidApplicationLicense(int $accountId, int $companyId, int $periodId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/paid-app/{$periodId}";

        $payload = [
            'startDate' => $licenseData['startDate'],
            'endDate' => $licenseData['endDate'],
        ];

        if (array_key_exists('seatLimit', $licenseData)) {
            $payload['seatLimit'] = $licenseData['seatLimit'];
        }

        Log::info("Updating paid application license (flexible)", [
            'payload' => $payload,
        ]);

        return $this->makeRequest('PUT', $endpoint, $payload);
    }

    public function __destruct()
    {
        if ($this->privateKey) {
            openssl_free_key($this->privateKey);
        }
    }
}
