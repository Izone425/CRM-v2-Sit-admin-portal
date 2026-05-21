<?php

namespace App\Console\Commands;

use App\Models\HrLicense;
use App\Models\Renewal;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoMappingRenewalV2 extends Command
{
    protected $signature = 'renewal-hrv2:auto-mapping';

    protected $description = 'Automatically create renewal records for HR V2 companies with expiring licenses that are not yet tracked';

    public function handle()
    {
        $this->info('Starting HRV2 auto-mapping process...');

        try {
            $companiesNeedingMapping = $this->getCompaniesNeedingMapping();

            if ($companiesNeedingMapping->isEmpty()) {
                $this->info('No companies found that need auto-mapping.');
                return Command::SUCCESS;
            }

            $this->info("Found {$companiesNeedingMapping->count()} companies that need auto-mapping.");

            $successCount = 0;
            $errorCount = 0;

            foreach ($companiesNeedingMapping as $company) {
                try {
                    $this->info("Processing: {$company->company_name} (SW ID: {$company->software_handover_id})");

                    $handover = SoftwareHandover::find($company->software_handover_id);

                    if (!$handover || !$handover->lead_id) {
                        $this->warn("  Skipped - no lead_id linked to software handover {$company->software_handover_id}");
                        $errorCount++;
                        continue;
                    }

                    // Check if a renewal already exists for this lead
                    $existingRenewal = Renewal::where('lead_id', $handover->lead_id)->first();

                    if ($existingRenewal) {
                        $this->info("  Skipped - renewal already exists for lead {$handover->lead_id}");
                        continue;
                    }

                    // Create the renewal record
                    Renewal::create([
                        'f_company_id' => 'SW_' . $company->software_handover_id,
                        'hr_version' => 2,
                        'software_handover_id' => $company->software_handover_id,
                        'lead_id' => $handover->lead_id,
                        'company_name' => $company->company_name,
                        'mapping_status' => 'completed_mapping',
                        'renewal_progress' => 'new',
                        'admin_renewal' => 'Fatimah Nurnabilah',
                        'follow_up_date' => now(),
                        'follow_up_counter' => true,
                    ]);

                    $successCount++;
                    $this->info("  Created renewal for lead {$handover->lead_id}");

                    Log::info('HRV2 auto-mapping created renewal', [
                        'lead_id' => $handover->lead_id,
                        'software_handover_id' => $company->software_handover_id,
                        'company_name' => $company->company_name,
                    ]);

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  Error: {$e->getMessage()}");
                    Log::error("HRV2 auto-mapping error for SW ID {$company->software_handover_id}: {$e->getMessage()}");
                }
            }

            $this->info("Auto-mapping completed: {$successCount} successful, {$errorCount} errors");
            Log::info("HRV2 auto-mapping summary: {$successCount} successful, {$errorCount} errors");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Auto-mapping process failed: {$e->getMessage()}");
            Log::error("HRV2 auto-mapping process error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function getCompaniesNeedingMapping()
    {
        $today = Carbon::now()->format('Y-m-d');
        $next90Days = Carbon::now()->addDays(90)->format('Y-m-d');

        // Get software_handover_ids with expiring PAID licenses
        $expiringCompanies = HrLicense::query()
            ->select([
                'hr_licenses.software_handover_id',
                DB::raw('ANY_VALUE(hr_licenses.company_name) as company_name'),
                DB::raw('MIN(hr_licenses.end_date) as earliest_expiry'),
                DB::raw('COUNT(*) as license_count'),
            ])
            ->where('hr_licenses.type', 'PAID')
            ->where('hr_licenses.status', 'Enabled')
            ->whereBetween('hr_licenses.end_date', [$today, $next90Days])
            ->groupBy('hr_licenses.software_handover_id')
            ->get();

        // Get lead_ids that already have renewal records
        $existingLeadIds = Renewal::whereNotNull('lead_id')
            ->pluck('lead_id')
            ->toArray();

        // Filter out companies whose software_handover already has a renewal via lead_id
        return $expiringCompanies->filter(function ($company) use ($existingLeadIds) {
            $handover = SoftwareHandover::find($company->software_handover_id);

            if (!$handover || !$handover->lead_id) {
                return false; // Can't map without a lead
            }

            return !in_array($handover->lead_id, $existingLeadIds);
        });
    }
}
