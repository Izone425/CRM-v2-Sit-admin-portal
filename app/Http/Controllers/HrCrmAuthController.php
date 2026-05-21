<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HrCrmAuthController extends Controller
{
    public function loginAsUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'userId' => 'required|integer',
            'companyId' => 'required|integer',
        ]);

        $email = $request->input('email');
        $userId = (int) $request->input('userId');
        $companyId = (int) $request->input('companyId');
        $crmUserId = (int) auth()->id();
        $crmUserName = auth()->user()->name ?? 'CRM User';

        Log::info('CRM Auth: Login as user request', [
            'email' => $email,
            'userId' => $userId,
            'companyId' => $companyId,
            'crmUserId' => $crmUserId,
        ]);

        // Audit trail: record who clicked the "Login as User" button.
        $relatedHandoverId = \App\Models\SoftwareHandover::where('hr_company_id', $companyId)
            ->orderByDesc('id')
            ->value('id');

        $auditLog = \App\Models\HrLoginAsUserLog::create([
            'causer_id' => $crmUserId,
            'causer_name' => $crmUserName,
            'target_email' => $email,
            'hr_user_id' => $userId,
            'hr_company_id' => $companyId,
            'software_handover_id' => $relatedHandoverId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'initiated',
        ]);

        // API Configuration
        $isProduction = config('app.env') === 'production';

        $apiUrl = $isProduction
            ? 'https://int-crmauth-hr.timeteccloud.com'
            : 'https://int-crmauth-hr-test.timeteccloud.com';

        $defaultRedirectUrl = $isProduction
            ? 'https://hr.timeteccloud.com/auth/crm-login'
            : 'https://hr-test.timeteccloud.com/auth/crm-login';

        $apiKey = '2wQ6E0cDU+AjoWIbWkZ1apOkfDrPkMdH3WlX0SNnaQU=';

        // Read private key - use environment-specific key
        $privateKeyPath = storage_path('keys/crm_auth_private_key.pem');

        if (!file_exists($privateKeyPath)) {
            $privateKeyPath = $isProduction
                ? public_path('hrv2-test/keys/crm_auth_private_key.pem')
                : public_path('hrv2-test/keys-sit/crm_auth_private_key.pem');
        }

        if (!file_exists($privateKeyPath)) {
            Log::error('CRM Auth: Private key not found');
            return back()->with('error', 'Private key not found for CRM authentication.');
        }

        $privateKey = file_get_contents($privateKeyPath);

        // Prepare payload
        $payload = [
            'email' => $email,
            'userId' => $userId,
            'companyId' => $companyId,
            'crmUserId' => $crmUserId,
            'crmUserName' => $crmUserName,
        ];
        $payloadJson = json_encode($payload);

        // Generate timestamp and signature
        $timestamp = gmdate('Y-m-d\TH:i:s.v\Z');
        $dataToSign = $payloadJson . $timestamp;

        // Sign with RSA-SHA256
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if (!$privateKeyResource) {
            Log::error('CRM Auth: Invalid private key - ' . openssl_error_string());
            return back()->with('error', 'Invalid private key for CRM authentication.');
        }

        openssl_sign($dataToSign, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
        $signatureBase64 = base64_encode($signature);

        Log::info('CRM Auth: Calling API', [
            'apiUrl' => $apiUrl . '/api/crmauth/loginas',
            'timestamp' => $timestamp,
            'privateKeyPath' => $privateKeyPath,
            'payloadKeys' => array_keys($payload),
        ]);

        // Make API request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl . '/api/crmauth/loginas',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payloadJson,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Api-Key: ' . $apiKey,
                'X-Signature: ' . $signatureBase64,
                'X-Timestamp: ' . $timestamp,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            Log::error('CRM Auth: CURL error - ' . $error);
            $auditLog->update(['status' => 'failed', 'error_message' => 'CURL error: ' . $error]);
            return back()->with('error', 'Failed to connect to CRM Auth API.');
        }
        curl_close($ch);

        // Parse response
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        Log::info('CRM Auth: API response', [
            'httpCode' => $httpCode,
            'bodyLength' => strlen($body),
            'headerLength' => $headerSize,
        ]);

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($body, true);

            // Extract token values from Set-Cookie headers
            $tokens = [];
            preg_match_all('/^Set-Cookie:\s*(.+)$/mi', $headers, $cookieMatches);
            foreach ($cookieMatches[1] ?? [] as $cookie) {
                if (preg_match('/^(auth_token|refresh_token)=([^;]+)/i', trim($cookie), $m)) {
                    $tokens[$m[1]] = $m[2];
                }
            }

            // Get redirect URL
            $redirectUrl = $responseData['redirectUrl'] ?? $defaultRedirectUrl;

            Log::info('CRM Auth: Success', [
                'redirectUrl' => $redirectUrl,
                'tokenKeys' => array_keys($tokens),
                'responseKeys' => array_keys($responseData ?? []),
            ]);

            $auditLog->update(['status' => 'success']);

            // CRM is on a different domain (192.168.x.x) so we cannot set cookies
            // for .timeteccloud.com directly. Redirect to the HR app with tokens
            // as query params so the HR app can set cookies on its own domain.
            $redirectUrl .= (str_contains($redirectUrl, '?') ? '&' : '?') . http_build_query($tokens);

            return redirect()->away($redirectUrl);
        }

        $errorBody = json_decode($body, true);
        $errorMessage = $errorBody['message'] ?? $body;
        Log::error('CRM Auth: API error', [
            'httpCode' => $httpCode,
            'errorMessage' => $errorMessage,
            'responseBody' => substr($body, 0, 500),
            'responseHeaders' => substr($headers, 0, 500),
        ]);

        $auditLog->update(['status' => 'failed', 'error_message' => "HTTP {$httpCode}: " . substr((string) $errorMessage, 0, 500)]);

        return back()->with('error', 'CRM Auth API error: ' . $errorMessage);
    }
}
