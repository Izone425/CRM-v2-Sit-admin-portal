<?php

namespace App\Console;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        \App\Console\Commands\AutoFollowUp::class,
        \App\Console\Commands\UpdateLeadStatus::class,
        \App\Console\Commands\UpdateSalesOrderStatus::class,
        \App\Console\Commands\ResetCompletedRenewals::class,
        \App\Console\Commands\InactivateExpiredResellerHandovers::class,
        \App\Console\Commands\SendPendingPaymentReminder::class,
        \App\Console\Commands\CheckFfHandoverPayment::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('leads:update-status')->dailyAt('00:01'); // Runs daily at 12:01 AM

        $schedule->command('repair-appointments:update-status')->dailyAt('00:03');

        $schedule->command('implementer-appointments:update-status')->dailyAt('00:05');

        $schedule->command('follow-up:auto')->weeklyOn(2, '10:00'); // Runs at Tuesday 10 AM

        $schedule->command('userleave:update')->everyThirtyMinutes(); // Runs every 30 Minutes

        $schedule->command('zoho:fetch-leads')->cron('*/4 * * * *'); // Runs every 4 minutes

        $schedule->command('repair:check-pending-status')->dailyAt('00:01');

        $schedule->command('handovers:check-delays')->dailyAt('00:01');

        // $schedule->command('handovers:check-pending-confirmation')->dailyAt('00:01');

        $schedule->command('handovers:sync')->everyThirtyMinutes();

        $schedule->command('renewals:reset-completed')->dailyAt('00:07'); // Reset completed renewals to new if new licenses start today

        $schedule->command('renewal:auto-mapping')->everyThirtyMinutes();

        // $schedule->command('renewal-hrv2:reset-completed')->dailyAt('00:08'); // V2: Reset completed renewals using hr_licenses table

        // $schedule->command('renewal-hrv2:auto-mapping')->everyThirtyMinutes(); // V2: Auto-map expiring hr_licenses to renewal records

        // $schedule->command('renewal-hrv2:process-auto-renewal')->dailyAt('00:15'); // V2: Auto-create invoices for expiring auto-renewal licenses

        $schedule->command('reseller:inactivate-expired')->dailyAt('00:10'); // Inactivate reseller handovers pending confirmation for more than 30 days

        $schedule->command('reseller-commission:process-credit-notes')->dailyAt('23:00'); // Auto-flag AP invoices with matching credit_notes as Credit Note

        // $schedule->command('handovers:process-full-payment-hardware-handover')->everyThirtyMinutes();

        $schedule->command('overtime:send-reminders')->weeklyOn(4, '16:00'); // Runs Thursday at 4:00 PM

        $schedule->command('overtime:send-reminders')->weeklyOn(5, '16:00'); // Runs Friday at 4:00 PM

        $schedule->command('renewal:send-dashboard-summary')
            ->dailyAt('08:00')
            ->weekdays();

        $schedule->command('emails:send-scheduled')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/scheduled-emails.log'));

        $schedule->command('calls:map-to-leads')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/call-mapping.log'));

        $schedule->command('ff-handover:check-payment')->everyMinute();

        $schedule->command('sales-order:update-status')->everyThirtyMinutes();

        $schedule->command('hrdf:sync-emails --days=1')->everyFiveMinutes();

        $schedule->command('hrdf:process-claim-payments')->everyFiveMinutes();

        $schedule->command('teams:fetch-recordings')
            ->everyThirtyMinutes()
            ->between('09:00', '22:00')
            ->weekdays();

        $schedule->command('training:fetch-recordings')->twiceDaily(14, 19);

        // $schedule->command('tickets:check-updates')
        //     ->everyMinute()
        //     ->withoutOverlapping()
        //     ->appendOutputTo(storage_path('logs/ticket-updates.log'));

        $schedule->command('notifications:sync-ticketing')
            ->everyMinute();

        $schedule->command('reseller:send-renewal-notification')->weeklyOn(1, '08:00'); // Monday 8 AM

        $schedule->command('reseller:send-pending-payment-reminder')->weeklyOn(1, '09:00'); // Monday 9 AM

        $schedule->command('reseller:send-fg-pending-payment-reminder')->weeklyOn(1, '09:00'); // Monday 9 AM

        // $schedule->command('hrdf:process-emails')
        //     ->everyFifteenMinutes()
        //     ->between('8:00', '18:00')
        //     ->weekdays();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
