<?php

namespace App\Console\Commands;

use App\Models\HrLicense;
use App\Models\Renewal;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetCompletedRenewalsV2 extends Command
{
    protected $signature = 'renewal-hrv2:reset-completed';

    protected $description = 'Reset completed renewals to new status if they have new HR licenses starting today or later';

    public function handle()
    {
        $this->info('Starting to check completed renewals (V2)...');

        $today = Carbon::now()->subDay()->format('Y-m-d');

        try {
            // Get all renewals with completed_renewal status that have a lead_id
            $completedRenewals = Renewal::where('renewal_progress', 'completed_renewal')
                ->whereNotNull('lead_id')
                ->get();

            $resetCount = 0;

            foreach ($completedRenewals as $renewal) {
                // Find software_handover via lead_id
                $handover = SoftwareHandover::where('lead_id', $renewal->lead_id)->first();

                if (!$handover) {
                    continue;
                }

                // Check if this handover has PAID licenses with start_date = today
                $hasNewLicenses = HrLicense::where('software_handover_id', $handover->id)
                    ->where('type', 'PAID')
                    ->where('start_date', '=', $today)
                    ->exists();

                if ($hasNewLicenses) {
                    $renewal->update([
                        'renewal_progress' => 'new',
                        'follow_up_date' => null,
                        'follow_up_counter' => false,
                        'task_status' => false,
                    ]);

                    $resetCount++;

                    $this->info("Reset renewal for company: {$renewal->company_name} (Lead ID: {$renewal->lead_id})");

                    Log::info('HRV2 Renewal reset to new', [
                        'lead_id' => $renewal->lead_id,
                        'software_handover_id' => $handover->id,
                        'company_name' => $renewal->company_name,
                        'previous_status' => 'completed_renewal',
                        'new_status' => 'new',
                    ]);
                }
            }

            $this->info("Completed! Reset {$resetCount} renewal(s) to 'new' status.");

            Log::info('HRV2 daily renewal reset completed', [
                'total_completed_renewals' => $completedRenewals->count(),
                'reset_count' => $resetCount,
                'date' => $today,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error resetting renewals: {$e->getMessage()}");

            Log::error('Error in hrv2:reset-completed command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
