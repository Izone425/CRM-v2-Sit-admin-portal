<?php

namespace App\Console\Commands;

use App\Models\ResellerHandoverFf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckFfHandoverPayment extends Command
{
    protected $signature = 'ff-handover:check-payment';

    protected $description = 'Check FF handover payment status by matching timetec_proforma_invoice with crm_invoice_details and auto-complete if paid';

    public function handle()
    {
        $handovers = ResellerHandoverFf::where('status', 'pending_quotation_confirmation')
            ->whereNotNull('timetec_proforma_invoice')
            ->get();

        $completedCount = 0;

        foreach ($handovers as $handover) {
            $invoice = DB::connection('frontenddb')
                ->table('crm_invoice_details')
                ->where('f_invoice_no', $handover->timetec_proforma_invoice)
                ->whereNotNull('f_payment_time')
                ->whereNotNull('f_payment_method')
                ->first();

            if ($invoice) {
                $handover->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $completedCount++;
                $this->info("FF Handover #{$handover->id} ({$handover->ff_id}) marked as completed. Invoice: {$handover->timetec_proforma_invoice}");
            }
        }

        $this->info("Completed: {$completedCount} / {$handovers->count()} checked.");
        Log::info("FF handover payment check completed. {$completedCount} handovers marked as completed.");

        return Command::SUCCESS;
    }
}
