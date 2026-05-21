<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ResellerHandover;
use Carbon\Carbon;

class InactivateExpiredResellerHandovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reseller:inactivate-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inactivate reseller handovers that have been in pending_confirmation for more than 30 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $expired = ResellerHandover::where('status', 'pending_quotation_confirmation')
            ->whereNotNull('ttpi_submitted_at')
            ->where('ttpi_submitted_at', '<', $thirtyDaysAgo)
            ->get();

        $count = $expired->count();

        foreach ($expired as $handover) {
            $handover->update(['status' => 'inactive']);
        }

        $this->info("Inactivated {$count} expired reseller handover(s).");

        return Command::SUCCESS;
    }
}
