<?php

namespace App\Services;

use App\Models\DistributorV2;
use App\Models\HrLicense;
use App\Models\PartnerApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class DistributorApprovalService
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
     * Approve a distributor application end-to-end.
     *
     * Mirrors ResellerApprovalService::approve() exactly except:
     *   - writes to distributor_v2 (not reseller_v2)
     *   - hr_licenses rows tagged license_category='Distributor'
     *   - handover_id prefix DST_xxxxxx
     *
     * CRM provisioning runs FIRST; on any CRM failure no local writes happen.
     */
    public function approve(PartnerApplication $application, int $bufferMonths, ?string $remark = null): array
    {
        $bufferMonths = max(1, $bufferMonths);

        $email = $application->email;

        if (empty($email)) {
            throw new RuntimeException('Application has no email address; cannot create a distributor account.');
        }

        if (DistributorV2::where('email', $email)->exists()) {
            throw new RuntimeException("A distributor account already exists for {$email}.");
        }

        $crm = $this->createCrmAccountAndLicense($application, $bufferMonths);

        $distributor = DB::transaction(function () use ($application, $remark, $email, $bufferMonths, $crm) {
            $plainPassword = (string) ($application->plain_password ?? Str::random(12));

            $application->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
                'review_remark' => $remark,
                'plain_password' => null,
            ]);
            $name = trim(($application->first_name ?? '') . ' ' . ($application->last_name ?? ''))
                ?: ($application->company_name ?? 'Distributor');

            $distributor = DistributorV2::create([
                'company_name' => $application->company_name,
                'name' => $name,
                'phone' => $application->mobile_phone,
                'email' => $email,
                'password' => Hash::make($plainPassword),
                'plain_password' => $crm['crmPasswordGenerated'],
                'reseller_id' => $this->resolveResellerId($application->company_name)
                    ?? $this->ensureLocalResellerRow($application->company_name),
                'partner_application_id' => $application->id,
                'modules' => $application->categories ?? [],
                'headcount' => $application->headcount,
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
                'hr_account_id' => $crm['accountId'],
                'hr_company_id' => $crm['companyId'],
                'hr_user_id' => $crm['userId'],
                'crm_buffer_license_id' => $crm['licenseSetId'],
            ]);

            $handoverId = 'DST_' . str_pad((string) $distributor->id, 6, '0', STR_PAD_LEFT);
            $headcount = (int) ($application->headcount ?? 0);

            foreach ($crm['applications'] as $module) {
                HrLicense::updateOrCreate(
                    [
                        'handover_id' => $handoverId,
                        'license_type' => "TimeTec {$module} (Trial)",
                    ],
                    [
                        'software_handover_id' => null,
                        'type' => 'TRIAL',
                        'company_name' => $distributor->company_name,
                        'license_category' => 'Distributor',
                        'unit' => $headcount,
                        'user_limit' => $headcount,
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
            }

            return $distributor;
        });

        Log::info('Distributor approval: complete', [
            'application_id' => $application->id,
            'distributor_id' => $distributor->id,
            'account_id' => $crm['accountId'],
            'company_id' => $crm['companyId'],
            'license_set_id' => $crm['licenseSetId'],
        ]);

        // Return shape matches ResellerApprovalService::approve() so the
        // shared Filament notification block in PartnerApplicationsTable
        // works without branching on partner_type a second time.
        return [
            'reseller' => $distributor,
            'dbProvisioned' => true,
            'dbError' => null,
            'crmPassword' => $crm['crmPasswordGenerated'],
            'accountId' => $crm['accountId'],
            'companyId' => $crm['companyId'],
        ];
    }

    protected function createCrmAccountAndLicense(PartnerApplication $application, int $bufferMonths): array
    {
        $crmService = app(HRV2LicenseService::class);

        [$countryId, $phoneCode, $timezone] = $this->resolveCountry($application->country);

        $rawPhone = $application->mobile_phone ?? $application->telephone ?? '';
        $cleanPhone = $this->cleanPhone($rawPhone, $phoneCode);

        if (empty($cleanPhone)) {
            throw new RuntimeException("Phone number '{$rawPhone}' is invalid; cannot create CRM account.");
        }

        $crmPassword = app(PasswordGeneratorService::class)->generate();
        $crmPasswordGenerated = $crmPassword;

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
            'notes' => "Trial license — distributor application #{$application->id}",
        ]);

        if (empty($licenseResult['success']) || empty($licenseResult['data'])) {
            Log::warning('Distributor approval: orphan CRM account created without buffer license', [
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

    public function mapModules(array $categories): array
    {
        return collect($categories)
            ->map(fn ($key) => self::MODULE_MAP[$key] ?? ucfirst($key))
            ->values()
            ->all();
    }

    protected function ensureLocalResellerRow(?string $companyName): ?int
    {
        if (empty($companyName)) {
            return null;
        }

        $existing = \App\Models\Reseller::whereRaw('UPPER(company_name) = ?', [strtoupper($companyName)])->first();
        if ($existing) {
            return (int) $existing->id;
        }

        return (int) \App\Models\Reseller::create(['company_name' => $companyName])->id;
    }

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

    protected function resolveCountry(?string $countryName): array
    {
        $countryId = 132;
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
            Log::warning('Distributor approval: CountryService failed, using Malaysia defaults', [
                'error' => $e->getMessage(),
            ]);
        }

        return [$countryId, $phoneCode, $timezone];
    }

    protected function cleanPhone(string $rawPhone, string $phoneCode): string
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        $phoneCodeDigits = preg_replace('/[^0-9]/', '', $phoneCode);

        if ($phoneCodeDigits !== '' && str_starts_with($cleanPhone, $phoneCodeDigits)) {
            $cleanPhone = substr($cleanPhone, strlen($phoneCodeDigits));
        }

        return ltrim($cleanPhone, '0');
    }
}
