<?php

namespace App\Services;

use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HRV2LicenseService
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
        // ISO8601 format like Node.js: 2024-10-30T10:30:45.123Z
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
            'signature' => $signature,
            'signature_length' => strlen($signature),
            'api_key' => $this->apiKey,
            'headers' => [
                'X-Api-Key' => $this->apiKey,
                'X-Signature' => substr($signature, 0, 50) . '...',
                'X-Timestamp' => $timestamp,
                'Content-Type' => 'application/json',
            ]
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
                'headers' => $response->headers(),
            ]);

            if ($response->successful()) {
                Log::info("CRM API Success", [
                    'endpoint' => $endpoint,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            // Some CRM environments do not expose invoice listing endpoint yet.
            if (
                $method === 'GET'
                && $statusCode === 404
                && str_contains($endpoint, '/invoices')
            ) {
                Log::warning("CRM API invoice list endpoint unavailable", [
                    'endpoint' => $endpoint,
                    'status' => $statusCode,
                ]);

                return [
                    'success' => false,
                    'error' => 'Invoice endpoint unavailable',
                    'status' => $statusCode,
                ];
            }

            Log::error("CRM API Error", [
                'endpoint' => $endpoint,
                'status' => $statusCode,
                'body' => $responseBody,
                'json' => $response->json(),
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
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create new account in CRM
     * POST /api/crm/account
     */
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

    public function __destruct()
    {
        if ($this->privateKey) {
            openssl_free_key($this->privateKey);
        }
    }

    /**
     * Add buffer license with flexible seatLimits
     * - If seatLimits is empty/null → ALL modules with UNLIMITED seats
     * - If seatLimits has specific modules → ONLY those modules activated
     * - null seat value → unlimited seats for that module
     * - numeric seat value → limited seats for that module
     */
    public function addBufferLicense(int $accountId, int $companyId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer";

        $payload = [
            'startDate' => $licenseData['startDate'],
            'endDate' => $licenseData['endDate'],
            'notes' => $licenseData['notes'] ?? null,
        ];

        if (isset($licenseData['applications']) && !empty($licenseData['applications'])) {
            $payload['applications'] = $licenseData['applications'];
        }

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
     * Update buffer license with flexible seatLimits
     */
    public function updateBufferLicense(int $accountId, int $companyId, int $licenseSetId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/buffer/{$licenseSetId}";

        $payload = [
            'startDate' => $licenseData['startDate'],
            'endDate' => $licenseData['endDate'],
            'notes' => $licenseData['notes'] ?? null,
        ];

        if (isset($licenseData['applications']) && !empty($licenseData['applications'])) {
            $payload['applications'] = $licenseData['applications'];
        }

        if (isset($licenseData['seatLimits']) && !empty($licenseData['seatLimits'])) {
            $payload['seatLimits'] = $licenseData['seatLimits'];
        }

        Log::info("Updating buffer license", [
            'account_id' => $accountId,
            'company_id' => $companyId,
            'license_set_id' => $licenseSetId,
            'payload' => $payload,
        ]);

        return $this->makeRequest('PUT', $endpoint, $payload);
    }

    /**
     * Add paid application license
     * - application: module name (e.g., 'Attendance')
     * - seatLimit: null = unlimited, number = limited
     */
    public function addPaidApplicationLicense(int $accountId, int $companyId, array $licenseData): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses/paid-app";

        $payload = [
            'application' => $licenseData['application'],
            'startDate' => $licenseData['startDate'],
            'endDate' => $licenseData['endDate'],
            'seatLimit' => $licenseData['seatLimit'],
        ];

        if (isset($licenseData['userId'])) {
            $payload['userId'] = $licenseData['userId'];
        }

        Log::info("Adding paid application license", [
            'account_id' => $accountId,
            'company_id' => $companyId,
            'endpoint' => $endpoint,
            'payload' => $payload
        ]);

        return $this->makeRequest('POST', $endpoint, $payload);
    }

    /**
     * Update paid application license
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

        Log::info("Updating paid application license", [
            'payload' => $payload,
        ]);

        return $this->makeRequest('PUT', $endpoint, $payload);
    }

    /**
     * Get company invoices from TimeTec Backend
     */
    public function getCompanyInvoices(int $accountId, int $companyId, array $params = []): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/invoices";

        if (!empty($params)) {
            $queryString = http_build_query($params);
            $endpoint .= "?{$queryString}";
        }

        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get Proforma Invoice details from TimeTec Backend
     */
    public function getProformaInvoiceDetails(int $accountId, int $companyId, string $invoiceNo): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/proforma-invoice/{$invoiceNo}";
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get company licenses from TimeTec Backend
     */
    public function getCompanyLicenses(int $accountId, int $companyId): array
    {
        $endpoint = "/api/crm/account/{$accountId}/company/{$companyId}/licenses";
        return $this->makeRequest('GET', $endpoint);
    }

    public function addBufferLicenses(
        SoftwareHandover $record,
        int $accountId,
        int $companyId,
        array $selectedModules,
        string $handoverId,
        ?string $startDate = null,
        ?string $endDate = null,
        int $bufferMonths = 1
    ): array {
        try {
            $bufferStartDate = $startDate ? Carbon::parse($startDate) : now();
            $bufferEndDate = $endDate ? Carbon::parse($endDate) : now()->copy()->addMonths($bufferMonths)->subDay();

            $allPiIds = $this->getAllPiIds($record);
            $seatLimits = $this->getSeatLimitsFromQuotations($allPiIds, $handoverId);

            $applications = [];
            $finalSeatLimits = [];

            $moduleMapping = [
                'ta' => 'Attendance',
                'tl' => 'Leave',
                'tc' => 'Claim',
                'tp' => 'Payroll',
                'tapp' => 'Appraisal',
                'thire' => 'Hire',
                'tacc' => 'Access',
                'tpbi' => 'PowerBI',
            ];

            foreach ($moduleMapping as $key => $appName) {
                if (empty($selectedModules[$key])) {
                    continue;
                }

                $seatCount = $seatLimits[$appName] ?? null;
                if ($seatCount !== null && $seatCount > 0) {
                    $applications[] = $appName;
                    $finalSeatLimits[$appName] = $seatCount;
                }
            }

            $licenseData = [
                'startDate' => $bufferStartDate->format('Y-m-d'),
                'endDate' => $bufferEndDate->format('Y-m-d'),
                'notes' => "Buffer License - {$handoverId} ({$bufferMonths} month" . ($bufferMonths > 1 ? 's' : '') . ")",
            ];

            if (!empty($applications)) {
                $licenseData['applications'] = $applications;
                $licenseData['seatLimits'] = $finalSeatLimits;
            }

            $result = $this->addBufferLicense($accountId, $companyId, $licenseData);

            if (!($result['success'] ?? false)) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Unknown error',
                    'results' => $result,
                ];
            }

            $licenseSetId = $result['data']['licenseSetId'] ?? null;

            return [
                'success' => true,
                'results' => $result,
                'license_set_id' => $licenseSetId,
                'seat_limits_applied' => $finalSeatLimits,
                'applications_with_limits' => $applications,
            ];
        } catch (\Exception $e) {
            Log::error('Buffer license creation failed', [
                'handover_id' => $handoverId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function addBufferLicenseUnlimited(
        SoftwareHandover $record,
        int $accountId,
        int $companyId,
        string $handoverId,
        int $bufferMonths = 1,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $bufferStartDate = $startDate ?? now()->format('Y-m-d');
            $bufferEndDate = $endDate ?? now()->addMonths($bufferMonths)->subDay()->format('Y-m-d');

            $licenseData = [
                'startDate' => $bufferStartDate,
                'endDate' => $bufferEndDate,
                'notes' => "Buffer License ({$bufferMonths} month(s)) - {$handoverId} - Unlimited Seats",
            ];

            $result = $this->addBufferLicense($accountId, $companyId, $licenseData);
            $licenseSetId = $result['data']['licenseSetId'] ?? null;

            return [
                'success' => (bool)($result['success'] ?? false),
                'error' => $result['error'] ?? null,
                'results' => $result,
                'license_set_id' => $licenseSetId,
                'success_count' => ($result['success'] ?? false) ? 1 : 0,
                'fail_count' => ($result['success'] ?? false) ? 0 : 1,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to add buffer license (unlimited)', [
                'handover_id' => $handoverId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function addPaidApplicationLicenses(
        SoftwareHandover $record,
        int $accountId,
        int $companyId,
        array $modules,
        string $handoverId,
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ): array {
        try {
            $productPiIds = $this->getProductPiIds($record);
            $allPiIds = !empty($productPiIds) ? $productPiIds : $this->getAllPiIds($record);

            if (empty($allPiIds)) {
                return ['success' => false, 'error' => 'No proforma invoices found'];
            }

            $moduleSegments = $this->getModuleLicenseSegmentsFromQuotations($allPiIds, $handoverId, $startDate);

            if (empty($moduleSegments)) {
                return ['success' => false, 'error' => 'No valid license periods found'];
            }

            $moduleMapping = [
                'ta' => ['app' => 'Attendance', 'codes' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tl' => ['app' => 'Leave', 'codes' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tc' => ['app' => 'Claim', 'codes' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tp' => ['app' => 'Payroll', 'codes' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tapp' => ['app' => 'Appraisal', 'codes' => ['TCL_APPRAISAL USER-NEW']],
                'thire' => ['app' => 'Hire', 'codes' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']],
                'tacc' => ['app' => 'Access', 'codes' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']],
                'tpbi' => ['app' => 'PowerBI', 'codes' => ['TCL_POWER BI']],
            ];

            $results = [];
            $paidLicenseIds = [];
            $successCount = 0;
            $failCount = 0;
            $skippedCount = 0;

            foreach ($moduleMapping as $moduleKey => $moduleInfo) {
                if (empty($modules[$moduleKey])) {
                    continue;
                }

                $appName = $moduleInfo['app'];
                $segments = $moduleSegments[$appName] ?? [];

                if (empty($segments)) {
                    $skippedCount++;
                    continue;
                }

                foreach ($segments as $segment) {
                    $paidStartDate = $segment['start_date'];
                    $paidEndDate = $endDate
                        ? $endDate->format('Y-m-d')
                        : $segment['end_date'];
                    $seatLimit = $segment['seat_limit'] ?? null;

                    if ($seatLimit === null || $seatLimit === 0) {
                        $skippedCount++;
                        continue;
                    }

                    $licenseData = [
                        'application' => $appName,
                        'startDate' => $paidStartDate,
                        'endDate' => $paidEndDate,
                        'userId' => auth()->id(),
                        'seatLimit' => $seatLimit,
                    ];

                    $result = $this->addPaidApplicationLicense($accountId, $companyId, $licenseData);
                    $results[$appName][] = array_merge($result, [
                        'year' => $segment['year_label'],
                        'subscription_period' => $segment['subscription_period'],
                        'start_date' => $segment['start_date'],
                        'end_date' => $segment['end_date'],
                        'seat_limit' => $segment['seat_limit'],
                    ]);

                    if ($result['success'] ?? false) {
                        $successCount++;
                        if (!empty($result['data']['periodId'])) {
                            $paidLicenseIds[] = $result['data']['periodId'];
                        }
                    } else {
                        $failCount++;
                    }
                }
            }

            return [
                'success' => $successCount > 0,
                'results' => $results,
                'paid_license_ids' => $paidLicenseIds,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'skipped_count' => $skippedCount,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to add paid application licenses', [
                'handover_id' => $handoverId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updatePaidLicense(
        int $accountId,
        int $companyId,
        int $periodId,
        string $startDate,
        string $endDate,
        ?int $seatLimit = null
    ): array {
        try {
            $licenseData = [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ];

            if ($seatLimit !== null) {
                $licenseData['seatLimit'] = $seatLimit;
            }

            return $this->updatePaidApplicationLicense($accountId, $companyId, $periodId, $licenseData);
        } catch (\Exception $e) {
            Log::error('Failed to update paid license', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getSeatLimitsFromQuotations(array $piIds, string $handoverId): array
    {
        $segments = $this->getModuleLicenseSegmentsFromQuotations($piIds, $handoverId);
        $seatLimits = [];

        foreach ($segments as $appName => $moduleSegments) {
            if (empty($moduleSegments)) {
                continue;
            }

            $seatLimits[$appName] = $moduleSegments[0]['seat_limit'] ?? null;
        }

        foreach ($seatLimits as $app => $seats) {
            if ($seats === 0) {
                $seatLimits[$app] = null;
            }
        }

        return $seatLimits;
    }

    public function getLicensePeriodsFromQuotations(array $piIds, string $handoverId): array
    {
        $licensePeriods = [];
        $segments = $this->getModuleLicenseSegmentsFromQuotations($piIds, $handoverId);

        foreach ($segments as $moduleName => $moduleSegments) {
            $totalMonths = array_sum(array_map(static fn ($segment) => (int) ($segment['subscription_period'] ?? 0), $moduleSegments));
            $lastSegment = end($moduleSegments);

            $licensePeriods[] = [
                'module_name' => $moduleName,
                'product_codes' => array_values(array_unique(array_merge(...array_map(static fn ($segment) => $segment['product_codes'] ?? [], $moduleSegments)))),
                'subscription_period' => $totalMonths,
                'end_date' => $lastSegment['end_date'] ?? null,
            ];
        }

        return $licensePeriods;
    }

    private function getModuleLicenseSegmentsFromQuotations(array $piIds, string $handoverId, ?\Carbon\Carbon $startDate = null): array
    {
        if (empty($piIds)) {
            return [];
        }

        $details = \App\Models\QuotationDetail::query()
            ->whereIn('quotation_id', $piIds)
            ->with('product')
            ->orderBy('quotation_id')
            ->orderBy('sort_order')
            ->get();

        $grouped = [];

        foreach ($details as $detail) {
            if (!$detail->product || !$detail->product->code) {
                continue;
            }

            $productCode = $detail->product->code;
            if (!$this->isUserLicense($productCode)) {
                continue;
            }

            $moduleName = $this->mapProductCodeToApp($productCode);
            if (!$moduleName || $moduleName === 'Full') {
                continue;
            }

            $yearLabel = $this->normalizeYearLabel($detail->year);
            $yearOrder = $this->extractYearOrder($yearLabel);
            $groupKey = $moduleName . '|' . $yearLabel;

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'module_name' => $moduleName,
                    'year_label' => $yearLabel,
                    'year_order' => $yearOrder,
                    'seat_limit' => (int) ($detail->quantity ?? 0),
                    'subscription_period' => (int) ($detail->subscription_period ?? $detail->product->subscription_period ?? 12),
                    'product_codes' => [$productCode],
                ];
            } else {
                // Avoid double counting mirrored rows by using max values per module/year group.
                $grouped[$groupKey]['seat_limit'] = max($grouped[$groupKey]['seat_limit'], (int) ($detail->quantity ?? 0));
                $grouped[$groupKey]['subscription_period'] = max(
                    $grouped[$groupKey]['subscription_period'],
                    (int) ($detail->subscription_period ?? $detail->product->subscription_period ?? 12)
                );
                $grouped[$groupKey]['product_codes'][] = $productCode;
            }
        }

        $segmentsByModule = [];
        foreach ($grouped as $item) {
            $moduleName = $item['module_name'];
            $segmentsByModule[$moduleName][] = [
                'year_label' => $item['year_label'],
                'year_order' => $item['year_order'],
                'seat_limit' => $item['seat_limit'],
                'subscription_period' => $item['subscription_period'],
                'product_codes' => array_values(array_unique($item['product_codes'])),
            ];
        }

        // Build year offsets from the full license-set timeline so modules that
        // only appear in Year 2+ still start in the correct year window.
        $monthsByYearOrder = [];
        foreach ($grouped as $item) {
            $yearOrder = (int) ($item['year_order'] ?? 1);
            $months = max(1, (int) ($item['subscription_period'] ?? 12));
            if (!isset($monthsByYearOrder[$yearOrder])) {
                $monthsByYearOrder[$yearOrder] = $months;
            } else {
                $monthsByYearOrder[$yearOrder] = max($monthsByYearOrder[$yearOrder], $months);
            }
        }

        ksort($monthsByYearOrder);

        $yearOffsets = [];
        $runningOffset = 0;
        foreach ($monthsByYearOrder as $yearOrder => $months) {
            $yearOffsets[(int) $yearOrder] = $runningOffset;
            $runningOffset += (int) $months;
        }

        $baseStart = ($startDate ?? now()->addMonth())->copy();
        foreach ($segmentsByModule as $moduleName => &$moduleSegments) {
            usort($moduleSegments, static function (array $a, array $b): int {
                return $a['year_order'] <=> $b['year_order'];
            });

            foreach ($moduleSegments as &$segment) {
                $months = max(1, (int) ($segment['subscription_period'] ?? 12));
                $yearOrder = (int) ($segment['year_order'] ?? 1);
                $offsetMonths = (int) ($yearOffsets[$yearOrder] ?? 0);

                $segmentStart = $baseStart->copy()->addMonths($offsetMonths);
                $segment['start_date'] = $segmentStart->format('Y-m-d');
                $segment['end_date'] = $segmentStart->copy()->addMonths($months)->subDay()->format('Y-m-d');

                Log::info('Module license segment detected', [
                    'handover_id' => $handoverId,
                    'module' => $moduleName,
                    'year' => $segment['year_label'],
                    'seat_limit' => $segment['seat_limit'],
                    'start_date' => $segment['start_date'],
                    'end_date' => $segment['end_date'],
                ]);
            }
            unset($segment);
        }
        unset($moduleSegments);

        return $segmentsByModule;
    }

    public function findEndDateForModule(array $productCodes, array $licensePeriods, string $startDate, string $handoverId): ?string
    {
        foreach ($licensePeriods as $period) {
            $intersection = array_intersect($productCodes, $period['product_codes']);
            if (!empty($intersection)) {
                return $period['end_date'];
            }
        }

        return null;
    }

    public function formatDuration(int $years, int $months): string
    {
        $parts = [];

        if ($years > 0) {
            $parts[] = $years . ' year' . ($years > 1 ? 's' : '');
        }

        if ($months > 0) {
            $parts[] = $months . ' month' . ($months > 1 ? 's' : '');
        }

        if (empty($parts)) {
            return '0 months';
        }

        return implode(' and ', $parts);
    }

    public function shouldModuleBeChecked(SoftwareHandover $record, array $productCodes): bool
    {
        $allPiIds = $this->getAllPiIds($record);
        if (empty($allPiIds)) {
            return false;
        }

        $quotations = \App\Models\Quotation::whereIn('id', $allPiIds)->get();

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)
                ->with('product')
                ->get();

            foreach ($details as $detail) {
                if (!$detail->product) {
                    continue;
                }

                if (in_array($detail->product->code, $productCodes)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getModulePeriodInfo(SoftwareHandover $record, array $productCodes): ?string
    {
        $allPiIds = $this->getAllPiIds($record);
        if (empty($allPiIds)) {
            return null;
        }

        $licensePeriods = $this->getLicensePeriodsFromQuotations($allPiIds, $record->project_code);
        $seatLimits = $this->getSeatLimitsFromQuotations($allPiIds, $record->project_code);

        $moduleMapping = [
            'Attendance' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL'],
            'Leave' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL'],
            'Claim' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL'],
            'Payroll' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL'],
            'Appraisal' => ['TCL_APPRAISAL USER-NEW'],
            'Hire' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL'],
            'Access' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL'],
            'PowerBI' => ['TCL_POWER BI'],
        ];

        $moduleName = null;
        foreach ($moduleMapping as $module => $codes) {
            $intersection = array_intersect($productCodes, $codes);
            if (!empty($intersection)) {
                $moduleName = $module;
                break;
            }
        }

        if (!$moduleName && in_array('TCL_FULL USER-NEW', $productCodes)) {
            $quotations = \App\Models\Quotation::whereIn('id', $allPiIds)->get();
            foreach ($quotations as $quotation) {
                $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)
                    ->with('product')
                    ->get();

                foreach ($details as $detail) {
                    if (!$detail->product) {
                        continue;
                    }

                    $detailCode = $detail->product->code;
                    foreach ($moduleMapping as $module => $codes) {
                        if (in_array($detailCode, $codes)) {
                            $moduleName = $module;
                            break 3;
                        }
                    }
                }
            }
        }

        if (!$moduleName) {
            return null;
        }

        foreach ($licensePeriods as $period) {
            if ($period['module_name'] !== $moduleName) {
                continue;
            }

            $totalMonths = $period['subscription_period'];
            $years = floor($totalMonths / 12);
            $months = $totalMonths % 12;
            $duration = $this->formatDuration($years, $months);

            $startDate = now()->addMonth()->format('d M Y');
            $endDateCarbon = now()->addMonth()->addMonths($totalMonths)->subDay();
            $endDate = $endDateCarbon->format('d M Y');
            $seatLimit = $seatLimits[$moduleName] ?? null;

            $info = "{$startDate} to {$endDate} ({$duration})";
            $info .= ($seatLimit !== null && $seatLimit > 0)
                ? " | {$seatLimit} seats"
                : ' | Unlimited seats';

            return $info;
        }

        return null;
    }

    private function getAllPiIds(SoftwareHandover $record): array
    {
        $allPiIds = [];

        $allPiIds = array_merge($allPiIds, $this->parsePiIds($record->software_hardware_pi ?? null));

        if (!empty($record->proforma_invoice_product)) {
            $allPiIds = array_merge($allPiIds, $this->parsePiIds($record->proforma_invoice_product));
        }

        $allPiIds = array_merge($allPiIds, $this->parsePiIds($record->non_hrdf_pi ?? null));

        if (!empty($record->proforma_invoice_hrdf)) {
            $allPiIds = array_merge($allPiIds, $this->parsePiIds($record->proforma_invoice_hrdf));
        }

        return array_values(array_unique(array_filter($allPiIds, static fn ($id) => $id !== null && $id !== '')));
    }

    private function getProductPiIds(SoftwareHandover $record): array
    {
        $piIds = [];
        $piIds = array_merge($piIds, $this->parsePiIds($record->software_hardware_pi ?? null));
        $piIds = array_merge($piIds, $this->parsePiIds($record->proforma_invoice_product ?? null));
        $piIds = array_merge($piIds, $this->parsePiIds($record->non_hrdf_pi ?? null));

        return array_values(array_unique(array_filter($piIds, static fn ($id) => $id !== null && $id !== '')));
    }

    private function parsePiIds($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = $value;
        for ($i = 0; $i < 2 && is_string($decoded); $i++) {
            $next = json_decode($decoded, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            $decoded = $next;
        }

        if (is_array($decoded)) {
            return array_map(static fn ($id) => (string) $id, $decoded);
        }

        return [];
    }

    private function normalizeYearLabel(?string $year): string
    {
        $trimmed = trim((string) $year);
        if ($trimmed === '') {
            return 'Year 1';
        }

        if (preg_match('/year\s*(\d+)/i', $trimmed, $matches)) {
            return 'Year ' . $matches[1];
        }

        return $trimmed;
    }

    private function extractYearOrder(string $yearLabel): int
    {
        if (preg_match('/year\s*(\d+)/i', $yearLabel, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }

    private function mapProductCodeToApp(string $productCode): ?string
    {
        $mapping = [
            'TCL_TA USER-NEW' => 'Attendance',
            'TCL_TA USER-ADDON' => 'Attendance',
            'TCL_TA USER-ADDON(R)' => 'Attendance',
            'TCL_TA USER-RENEWAL' => 'Attendance',
            'TCL_LEAVE USER-NEW' => 'Leave',
            'TCL_LEAVE USER-ADDON' => 'Leave',
            'TCL_LEAVE USER-ADDON(R)' => 'Leave',
            'TCL_LEAVE USER-RENEWAL' => 'Leave',
            'TCL_CLAIM USER-NEW' => 'Claim',
            'TCL_CLAIM USER-ADDON' => 'Claim',
            'TCL_CLAIM USER-ADDON(R)' => 'Claim',
            'TCL_CLAIM USER-RENEWAL' => 'Claim',
            'TCL_PAYROLL USER-NEW' => 'Payroll',
            'TCL_PAYROLL USER-ADDON' => 'Payroll',
            'TCL_PAYROLL USER-ADDON(R)' => 'Payroll',
            'TCL_PAYROLL USER-RENEWAL' => 'Payroll',
            'TCL_APPRAISAL USER-NEW' => 'Appraisal',
            'TCL_HIRE-NEW' => 'Hire',
            'TCL_HIRE-RENEWAL' => 'Hire',
            'TCL_ACCESS-NEW' => 'Access',
            'TCL_ACCESS-RENEWAL' => 'Access',
            'TCL_POWER BI' => 'PowerBI',
            'TCL_FULL USER-NEW' => 'Full',
        ];

        return $mapping[$productCode] ?? null;
    }

    private function isUserLicense(string $productCode): bool
    {
        $userLicenseCodes = [
            'TCL_TA USER-NEW',
            'TCL_TA USER-ADDON',
            'TCL_TA USER-ADDON(R)',
            'TCL_TA USER-RENEWAL',
            'TCL_LEAVE USER-NEW',
            'TCL_LEAVE USER-ADDON',
            'TCL_LEAVE USER-ADDON(R)',
            'TCL_LEAVE USER-RENEWAL',
            'TCL_CLAIM USER-NEW',
            'TCL_CLAIM USER-ADDON',
            'TCL_CLAIM USER-ADDON(R)',
            'TCL_CLAIM USER-RENEWAL',
            'TCL_PAYROLL USER-NEW',
            'TCL_PAYROLL USER-ADDON',
            'TCL_PAYROLL USER-ADDON(R)',
            'TCL_PAYROLL USER-RENEWAL',
            'TCL_APPRAISAL USER-NEW',
            'TCL_HIRE-NEW',
            'TCL_HIRE-RENEWAL',
            'TCL_ACCESS-NEW',
            'TCL_ACCESS-RENEWAL',
            'TCL_POWER BI',
            'TCL_FULL USER-NEW',
        ];

        return in_array($productCode, $userLicenseCodes);
    }
}
