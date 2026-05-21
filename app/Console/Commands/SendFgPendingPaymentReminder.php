<?php

namespace App\Console\Commands;

use App\Mail\FgPendingPaymentReminder;
use App\Models\ResellerHandoverFg;
use App\Models\ResellerV2;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendFgPendingPaymentReminder extends Command
{
    protected $signature = 'reseller:send-fg-pending-payment-reminder';

    protected $description = 'Send weekly email reminder to resellers with pending payment FG handovers';

    public function handle()
    {
        $handovers = ResellerHandoverFg::where('status', 'pending_reseller_payment')
            ->orderBy('updated_at', 'asc')
            ->get();

        if ($handovers->isEmpty()) {
            $this->info('No pending reseller payment FG handovers found.');
            return;
        }

        $sentCount = 0;

        $grouped = $handovers->groupBy('reseller_id');

        foreach ($grouped as $resellerId => $resellerHandovers) {
            $resellerCompanyName = $resellerHandovers->first()->reseller_company_name ?? 'Unknown';

            $reseller = ResellerV2::where('reseller_id', $resellerId)->first();
            $toEmail = $reseller?->email ?? 'faiz@timeteccloud.com';

            try {
                Mail::to($toEmail)
                    ->bcc(['zilih.ng@timeteccloud.com', 'faiz@timeteccloud.com', 'fatimah.tarmizi@timeteccloud.com'])
                    ->send(new FgPendingPaymentReminder($resellerHandovers, $resellerCompanyName));

                $sentCount++;

                Log::info('Pending payment reminder sent (FG)', [
                    'reseller_id' => $resellerId,
                    'reseller_company' => $resellerCompanyName,
                    'to_email' => $toEmail,
                    'handover_count' => $resellerHandovers->count(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send pending payment reminder (FG)', [
                    'reseller_id' => $resellerId,
                    'reseller_company' => $resellerCompanyName,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed to send to {$resellerCompanyName} (FG): {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sentCount} FG pending payment reminder email(s) for {$handovers->count()} handover(s).");
    }
}
