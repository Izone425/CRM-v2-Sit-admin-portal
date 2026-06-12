<?php

namespace App\Services;

use App\Models\HrLicense;
use App\Models\PartnerApplication;
use App\Models\ResellerV2;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ResellerApprovalService
{
    /**
     * Maps partner application category keys to CRM application/module names.
     */
    protected const MODULE_MAP = [
        'attendance' => 'Attendance',
        'leave' => 'Leave',
        'claim' => 'Claim',
        'payroll' => 'Payroll',
    ];

    /**
     * Approve a reseller application end-to-end.
     *
     * CRM provisioning runs FIRST (createAccount + addBufferLicense). On any
     * CRM failure the method throws — no local DB state changes, the admin
     * sees a red Filament danger notification with the actual CRM error and
     * can fix the underlying data (e.g. duplicate phone) before re-trying.
     *
     * Only after CRM succeeds do we mark the partner_application approved,
     * create the reseller_v2 row (with CRM IDs already populated, so the row
     * is never half-written), and create the hr_licenses row that surfaces
     * the reseller's buffer license on /admin/hr-license under
     * Category=Reseller.
     *
     * @return array{reseller: ResellerV2, dbProvisioned: bool, dbError: ?string, crmPassword: ?string}
     *
     * @throws RuntimeException on missing email, pre-existing reseller, or any CRM failure.
     */
    public function approve(PartnerApplication $application, int $bufferMonths, ?string $remark = null): array
    {
        $bufferMonths = max(1, $bufferMonths);

        $email = $application->email;

        if (empty($email)) {
            throw new RuntimeException('Application has no email address; cannot create a reseller account.');
        }

        if (ResellerV2::where('email', $email)->exists()) {
            throw new RuntimeException("A reseller account already exists for {$email}.");
        }

        // 1. CRM-side provisioning FIRST. Throws on any CRM failure; no local writes have happened yet.
        $crm = $this->createCrmAccountAndLicense($application, $bufferMonths);

        // 2. CRM succeeded — persist all local state in a single transaction with the CRM IDs in hand.
        $reseller = DB::transaction(function () use ($application, $remark, $email, $bufferMonths, $crm) {
            $application->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_remark' => $remark,
            ]);

            $plainPassword = (string) ($application->password ?? Str::random(12));
            $name = trim(($application->first_name ?? '') . ' ' . ($application->last_name ?? ''))
                ?: ($application->company_name ?? 'Reseller');

            $reseller = ResellerV2::create([
                'company_name' => $application->company_name,
                'name' => $name,
                'phone' => $application->mobile_phone,
                'email' => $email,
                'password' => Hash::make($plainPassword),
                'plain_password' => $plainPassword,
                'reseller_id' => $this->resolveResellerId($application->company_name),
                'partner_application_id' => $application->id,
                'modules' => $application->categories ?? [],
                'headcount' => $application->headcount,
                // Billing/feature fields default the same as the manual Create Reseller flow.
                'payment_type' => null,
                'email_notification' => 'no',
                'trial_account_feature' => 'disable',
                'installation_payment_feature' => 'disable',
                'renewal_quotation' => 'disable',
                'bill_as_reseller' => 'disable',
                'bill_as_end_user' => 'disable',
                'usd_with_quotation' => 'disable',
                'usd_with_invoice' => 'disable',
                'reseller_commission' => 'disable',
                'block_payment_gateway' => 'pending',
                'bypass_invoice' => 'no',
                'status' => 'active',
                'email_verified_at' => now(),
                // CRM IDs populated from the successful CRM call above.
                'hr_account_id' => $crm['accountId'],
                'hr_company_id' => $crm['companyId'],
                'hr_user_id' => $crm['userId'],
                'crm_buffer_license_id' => $crm['licenseSetId'],
            ]);

            // Local All-Licenses-page row so the reseller's buffer license is
            // visible at /admin/hr-license filtered by Category=Reseller.
            // Mirrors the Subscriber pattern in SoftwareHandoverExportController.
            HrLicense::updateOrCreate(
                ['handover_id' => 'RSL_' . str_pad((string) $reseller->id, 6, '0', STR_PAD_LEFT)],
                [
                    'software_handover_id' => null,
                    'type' => 'TRIAL',
                    'company_name' => $reseller->company_name,
                    'license_category' => 'Reseller',
                    'license_type' => 'TimeTec (' . implode(', ', $crm['applications']) . ') (Trial)',
                    'unit' => (int) ($application->headcount ?? 0),
                    'user_limit' => (int) ($application->headcount ?? 0),
                    'total_user' => 0,
                    'total_login' => 0,
                    'month' => $bufferMonths,
                    'start_date' => $crm['startDate'],
                    'end_date' => $crm['endDate'],
                    'status' => 'Enabled',
                    'auto_renewal' => 'Disabled',
                    'license_set_id' => $crm['licenseSetId'],
                ]
            );

            return $reseller;
        });

        Log::info('Reseller approval: complete', [
            'application_id' => $application->id,
            'reseller_id' => $reseller->id,
            'account_id' => $crm['accountId'],
            'company_id' => $crm['companyId'],
            'license_set_id' => $crm['licenseSetId'],
        ]);

        return [
            'reseller' => $reseller,
            'dbProvisioned' => true,
            'dbError' => null,
            'crmPassword' => $crm['crmPasswordGenerated'],
        ];
    }

    /**
     * Call the CRM API to create the HR account + buffer/trial license for a
     * reseller application. NO local DB writes — returns the CRM IDs (and any
     * generated password) so the caller can persist them in a single
     * transaction alongside the rest of the local state.
     *
     * @return array{
     *   accountId: int,
     *   companyId: int,
     *   userId: ?string,
     *   licenseSetId: ?string,
     *   crmPasswordGenerated: ?string,
     *   applications: array<int, string>,
     *   startDate: string,
     *   endDate: string,
     * }
     *
     * @throws RuntimeException on any CRM failure — the message is prefixed
     *                          "CRM database creation failed:" so the admin's
     *                          danger notification reads clearly.
     */
    protected function createCrmAccountAndLicense(PartnerApplication $application, int $bufferMonths): array
    {
        $crmService = app(HRV2LicenseService::class);

        [$countryId, $phoneCode, $timezone] = $this->resolveCountry($application->country);

        $rawPhone = $application->mobile_phone ?? $application->telephone ?? '';
        $cleanPhone = $this->cleanPhone($rawPhone, $phoneCode);

        if (empty($cleanPhone)) {
            throw new RuntimeException("Phone number '{$rawPhone}' is invalid; cannot create CRM account.");
        }

        // The reseller logs in with their chosen password. The CRM HR account
        // requires a complex password — if the applicant's doesn't qualify,
        // generate a compliant one and surface it to the admin.
        $crmPasswordGenerated = null;
        $crmPassword = (string) $application->password;
        if (!$this->isCompliantPassword($crmPassword)) {
            $crmPassword = $this->generateCompliantPassword();
            $crmPasswordGenerated = $crmPassword;
        }

        // 1. Create CRM HR account.
        $accountResult = $crmService->createAccount([
            'company_name' => $application->company_name,
            'country_id' => $countryId,
            'name' => trim(($application->first_name ?? '') . ' ' . ($application->last_name ?? '')) ?: 'Admin',
            'email' => $application->email,
            'password' => $crmPassword,
            'phone_code' => $phoneCode,
            'phone' => $cleanPhone,
            'timezone' => $timezone,
        ]);

        if (empty($accountResult['success']) || empty($accountResult['data'])) {
            throw new RuntimeException(
                'CRM database creation failed: ' . ($accountResult['error'] ?? 'CRM API returned no data when creating the account.')
            );
        }

        $accountId = (int) $accountResult['data']['accountId'];
        $companyId = (int) $accountResult['data']['companyId'];
        $userId = $accountResult['data']['userId'] ?? null;

        // 2. Create buffer/trial license sized from the application's modules + headcount.
        $applications = $this->mapModules($application->categories ?? []);
        $seatLimits = [];
        foreach ($applications as $moduleName) {
            $seatLimits[$moduleName] = $application->headcount;
        }

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addMonths($bufferMonths)->subDay()->format('Y-m-d');

        $licenseResult = $crmService->addBufferLicense($accountId, $companyId, [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'applications' => $applications,
            'seatLimits' => $seatLimits,
            'notes' => "Trial license — reseller application #{$application->id}",
        ]);

        if (empty($licenseResult['success']) || empty($licenseResult['data'])) {
            // CRM account exists but the license call failed — orphan on the
            // CRM side. Log the IDs so an admin can clean it up; no local row
            // gets created here.
            Log::warning('Reseller approval: orphan CRM account created without buffer license', [
                'application_id' => $application->id,
                'email' => $application->email,
                'account_id' => $accountId,
                'company_id' => $companyId,
                'error' => $licenseResult['error'] ?? null,
            ]);

            throw new RuntimeException(
                'CRM database creation failed (buffer license): ' . ($licenseResult['error'] ?? 'CRM API returned no data when creating the buffer license.')
            );
        }

        return [
            'accountId' => $accountId,
            'companyId' => $companyId,
            'userId' => $userId,
            'licenseSetId' => $licenseResult['data']['licenseSetId'] ?? null,
            'crmPasswordGenerated' => $crmPasswordGenerated,
            'applications' => $applications,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * Map application category keys to CRM module names.
     *
     * @param  array<int, string>  $categories
     * @return array<int, string>
     */
    public function mapModules(array $categories): array
    {
        return collect($categories)
            ->map(fn ($key) => self::MODULE_MAP[$key] ?? ucfirst($key))
            ->values()
            ->all();
    }

    /**
     * Resolve a bound reseller_id from the frontenddb by company name, matching
     * the logic used by the manual Create Reseller action.
     */
    protected function resolveResellerId(?string $companyName): ?int
    {
        if (empty($companyName)) {
            return null;
        }

        $resellerLink = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->whereRaw('UPPER(reseller_name) = ?', [strtoupper($companyName)])
            ->first();

        if ($resellerLink) {
            return (int) $resellerLink->reseller_id;
        }

        $dealer = DB::connection('frontenddb')
            ->table('crm_customer')
            ->whereRaw('UPPER(f_company_name) = ?', [strtoupper($companyName)])
            ->whereRaw('UPPER(f_company_type) = ?', ['DEALER'])
            ->first();

        return $dealer ? (int) $dealer->company_id : null;
    }

    /**
     * Resolve [countryId, phoneCode, timezone] from a country name, with a
     * Malaysia fallback — mirrors createCRMAccountForHandover().
     *
     * @return array{0: int, 1: string, 2: string}
     */
    protected function resolveCountry(?string $countryName): array
    {
        $countryId = 132; // Malaysia default
        $phoneCode = '+60';
        $timezone = 'Asia/Kuala_Lumpur';

        try {
            $countryService = app(CountryService::class);
            $countries = $countryService->getCountries();
            $lookup = $countryName ?: 'Malaysia';
            $countryData = collect($countries)->firstWhere('name', $lookup)
                ?? collect($countries)->firstWhere('iso3', $lookup)
                ?? collect($countries)->firstWhere('id', 132);

            if ($countryData) {
                $countryId = (int) $countryData['id'];
                $phoneCode = $countryData['phone_code'] ?? '+60';
                $timezone = $countryData['timezone'] ?? 'Asia/Kuala_Lumpur';
            }
        } catch (\Throwable $e) {
            Log::warning('Reseller approval: CountryService failed, using Malaysia defaults', [
                'error' => $e->getMessage(),
            ]);
        }

        return [$countryId, $phoneCode, $timezone];
    }

    /**
     * Strip a phone number down to local digits (no country code, no leading 0).
     */
    protected function cleanPhone(string $rawPhone, string $phoneCode): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        $phoneCodeDigits = preg_replace('/[^0-9]/', '', $phoneCode);

        if ($phoneCodeDigits !== '' && str_starts_with($cleanPhone, $phoneCodeDigits)) {
            $cleanPhone = substr($cleanPhone, strlen($phoneCodeDigits));
        }

        return ltrim($cleanPhone, '0');
    }

    protected function isCompliantPassword(?string $password): bool
    {
        if (!is_string($password)) {
            return false;
        }

        return strlen($password) >= 12
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }

    protected function generateCompliantPassword(): string
    {
        return Str::upper(Str::random(4))
            . Str::lower(Str::random(4))
            . random_int(1000, 9999)
            . '!@';
    }
}
