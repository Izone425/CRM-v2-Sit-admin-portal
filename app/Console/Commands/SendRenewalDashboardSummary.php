<?php

namespace App\Console\Commands;

use App\Models\Renewal;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendRenewalDashboardSummary extends Command
{
    protected $signature = 'renewal:send-dashboard-summary {--to=renewal@timteccloud.com}';

    protected $description = 'Send admin renewal dashboard main tab counts via email';

    public function handle(): int
    {
        $to = $this->option('to') ?: 'renewal@timteccloud.com';

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid recipient email: {$to}");
            return self::FAILURE;
        }

        $summary = $this->buildSummary();

        try {
            Mail::send('emails.renewal-dashboard-summary', [
                'summary' => $summary,
                'generatedAt' => now(),
            ], function (Message $message) use ($to) {
                $message->to($to)
                    ->subject('Admin Renewal Dashboard Summary');
            });

            $this->info("Renewal dashboard summary email sent to {$to}");

            Log::info('Renewal dashboard summary email sent', [
                'to' => $to,
                'summary' => $summary,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Failed to send renewal dashboard summary email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            $this->error('Failed to send renewal dashboard summary email: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function buildSummary(): array
    {
        $myrResellerIds = $this->getResellerCompanyIds('MYR');
        $usdResellerIds = $this->getResellerCompanyIds('USD');
        $myrNonResellerIds = $this->getNonResellerCompanyIds('MYR');
        $usdNonResellerIds = $this->getNonResellerCompanyIds('USD');

        return [
            'Reseller' => [
                [
                    'label' => 'Pending Confirmation Reseller (MYR)',
                    'count' => $this->getMainTabCount($myrResellerIds, 'pending_confirmation'),
                ],
                [
                    'label' => 'Pending Payment Reseller (MYR)',
                    'count' => $this->getMainTabCount($myrResellerIds, 'pending_payment'),
                ],
                [
                    'label' => 'Pending Confirmation Reseller (USD)',
                    'count' => $this->getMainTabCount($usdResellerIds, 'pending_confirmation'),
                ],
                [
                    'label' => 'Pending Payment Reseller (USD)',
                    'count' => $this->getMainTabCount($usdResellerIds, 'pending_payment'),
                ],
            ],
            'End User' => [
                [
                    'label' => 'Pending Confirmation End User (MYR)',
                    'count' => $this->getMainTabCount($myrNonResellerIds, 'pending_confirmation'),
                ],
                [
                    'label' => 'Pending Payment End User (MYR)',
                    'count' => $this->getMainTabCount($myrNonResellerIds, 'pending_payment'),
                ],
                [
                    'label' => 'Pending Confirmation End User (USD)',
                    'count' => $this->getMainTabCount($usdNonResellerIds, 'pending_confirmation'),
                ],
                [
                    'label' => 'Pending Payment End User (USD)',
                    'count' => $this->getMainTabCount($usdNonResellerIds, 'pending_payment'),
                ],
            ],
        ];
    }

    private function getMainTabCount(array $companyIds, string $renewalProgress): int
    {
        if (empty($companyIds)) {
            return 0;
        }

        return Renewal::query()
            ->whereIn('f_company_id', $companyIds)
            ->where('renewal_progress', $renewalProgress)
            ->where('follow_up_counter', true)
            ->where('mapping_status', 'completed_mapping')
            ->whereDate('follow_up_date', '<=', today())
            ->count();
    }

    private function getResellerCompanyIds(string $currency): array
    {
        $companyIds = DB::connection('frontenddb')->table('crm_expiring_license')
            ->select('f_company_id')
            ->where('f_currency', $currency)
            ->whereDate('f_expiry_date', '>=', today())
            ->whereDate('f_expiry_date', '<=', today()->addDays(90))
            ->distinct()
            ->pluck('f_company_id')
            ->flatMap(function ($id) {
                $withoutZeros = (string) (int) $id;
                $withZeros = str_pad($withoutZeros, 10, '0', STR_PAD_LEFT);
                return [$withoutZeros, $withZeros];
            })
            ->toArray();

        $resellerIds = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->whereIn('f_id', $companyIds)
            ->pluck('f_id')
            ->toArray();

        return array_values(array_intersect($companyIds, $resellerIds));
    }

    private function getNonResellerCompanyIds(string $currency): array
    {
        $companyIds = DB::connection('frontenddb')->table('crm_expiring_license')
            ->select('f_company_id')
            ->where('f_currency', $currency)
            ->whereDate('f_expiry_date', '>=', today())
            ->whereDate('f_expiry_date', '<=', today()->addDays(90))
            ->distinct()
            ->pluck('f_company_id')
            ->flatMap(function ($id) {
                $withoutZeros = (string) (int) $id;
                $withZeros = str_pad($withoutZeros, 10, '0', STR_PAD_LEFT);
                return [$withoutZeros, $withZeros];
            })
            ->toArray();

        $resellerIds = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->whereIn('f_id', $companyIds)
            ->pluck('f_id')
            ->toArray();

        return array_values(array_diff($companyIds, $resellerIds));
    }
}
