<?php

namespace App\Console\Commands;

use App\Mail\ResellerReminderListAnnouncement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AnnounceResellerReminderList extends Command
{
    /**
     * Live blast — sends the Reminder List announcement to every active reseller
     * in reseller_v2 (status='active', email present).
     */
    protected $signature = 'reseller:announce-reminder-list';

    protected $description = 'Send the Reminder List announcement email to all active resellers.';

    public function handle(): int
    {
        $resellers = DB::table('reseller_v2')
            ->where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($resellers->isEmpty()) {
            $this->warn('No active resellers found.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($resellers as $reseller) {
            try {
                Mail::to($reseller->email)
                    ->bcc([
                        'faiz@timeteccloud.com',
                        'fatimah.tarmizi@timeteccloud.com',
                    ])
                    ->send(new ResellerReminderListAnnouncement($reseller->company_name));

                $sent++;

                Log::info('Reseller reminder-list announcement sent', [
                    'reseller' => $reseller->company_name,
                    'email'    => $reseller->email,
                ]);
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed: {$reseller->company_name} ({$reseller->email}) - {$e->getMessage()}");
                Log::error('Reseller reminder-list announcement failed', [
                    'reseller' => $reseller->company_name,
                    'email'    => $reseller->email,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $this->info("Reminder List announcement: sent={$sent}, failed={$failed}");
        return self::SUCCESS;
    }
}
