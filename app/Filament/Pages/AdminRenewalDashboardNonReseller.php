<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Renewal;
use Illuminate\Support\Facades\DB;

class AdminRenewalDashboardNonReseller extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.adminrenewalnonreseller';
    protected static ?string $title = 'Admin Renewal Dashboard - End User';
    protected static ?string $slug = 'admin-renewal-dashboard-non-reseller';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from main navigation since we're using custom sidebar
    }

    protected function getViewData(): array
    {
        // Fetch non-reseller company IDs per currency ONCE
        $myrNonResellerIds = $this->getNonResellerCompanyIds('MYR');
        $usdNonResellerIds = $this->getNonResellerCompanyIds('USD');

        // Base query conditions shared by all counts
        $baseConditions = fn ($query) => $query
            ->where('follow_up_counter', true)
            ->where('mapping_status', 'completed_mapping');

        // MYR counts (pending_confirmation)
        $followUpTodayMYR = $this->getCount($myrNonResellerIds, 'pending_confirmation', 'today', $baseConditions);
        $followUpOverdueMYR = $this->getCount($myrNonResellerIds, 'pending_confirmation', 'overdue', $baseConditions);
        $followUpFutureMYR = $this->getCount($myrNonResellerIds, 'pending_confirmation', 'upcoming', $baseConditions);
        $followUpAllMYR = $this->getCount($myrNonResellerIds, 'pending_confirmation', 'all', $baseConditions);

        // USD counts (pending_confirmation)
        $followUpTodayUSD = $this->getCount($usdNonResellerIds, 'pending_confirmation', 'today', $baseConditions);
        $followUpOverdueUSD = $this->getCount($usdNonResellerIds, 'pending_confirmation', 'overdue', $baseConditions);
        $followUpFutureUSD = $this->getCount($usdNonResellerIds, 'pending_confirmation', 'upcoming', $baseConditions);
        $followUpAllUSD = $this->getCount($usdNonResellerIds, 'pending_confirmation', 'all', $baseConditions);

        // MYR V2 counts (pending_payment)
        $followUpTodayMYRv2 = $this->getCount($myrNonResellerIds, 'pending_payment', 'today', $baseConditions);
        $followUpOverdueMYRv2 = $this->getCount($myrNonResellerIds, 'pending_payment', 'overdue', $baseConditions);
        $followUpFutureMYRv2 = $this->getCount($myrNonResellerIds, 'pending_payment', 'upcoming', $baseConditions);
        $followUpAllMYRv2 = $this->getCount($myrNonResellerIds, 'pending_payment', 'all', $baseConditions);

        // USD V2 counts (pending_payment)
        $followUpTodayUSDv2 = $this->getCount($usdNonResellerIds, 'pending_payment', 'today', $baseConditions);
        $followUpOverdueUSDv2 = $this->getCount($usdNonResellerIds, 'pending_payment', 'overdue', $baseConditions);
        $followUpFutureUSDv2 = $this->getCount($usdNonResellerIds, 'pending_payment', 'upcoming', $baseConditions);
        $followUpAllUSDv2 = $this->getCount($usdNonResellerIds, 'pending_payment', 'all', $baseConditions);

        return [
            'followUpTodayMYR' => $followUpTodayMYR,
            'followUpOverdueMYR' => $followUpOverdueMYR,
            'followUpFutureMYR' => $followUpFutureMYR,
            'followUpAllMYR' => $followUpAllMYR,
            'followUpTodayUSD' => $followUpTodayUSD,
            'followUpOverdueUSD' => $followUpOverdueUSD,
            'followUpFutureUSD' => $followUpFutureUSD,
            'followUpAllUSD' => $followUpAllUSD,
            'followUpTodayMYRv2' => $followUpTodayMYRv2,
            'followUpOverdueMYRv2' => $followUpOverdueMYRv2,
            'followUpFutureMYRv2' => $followUpFutureMYRv2,
            'followUpAllMYRv2' => $followUpAllMYRv2,
            'followUpTodayUSDv2' => $followUpTodayUSDv2,
            'followUpOverdueUSDv2' => $followUpOverdueUSDv2,
            'followUpFutureUSDv2' => $followUpFutureUSDv2,
            'followUpAllUSDv2' => $followUpAllUSDv2,
            'followUpTotalMYR' => $followUpTodayMYR + $followUpOverdueMYR,
            'followUpTotalUSD' => $followUpTodayUSD + $followUpOverdueUSD,
            'followUpTotalMYRv2' => $followUpTodayMYRv2 + $followUpOverdueMYRv2,
            'followUpTotalUSDv2' => $followUpTodayUSDv2 + $followUpOverdueUSDv2,
        ];
    }

    /**
     * Get non-reseller company IDs for a given currency (shared query, called once per currency).
     */
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

        // Non-reseller = exclude reseller IDs
        return array_diff($companyIds, $resellerIds);
    }

    /**
     * Get count for a specific combination of company IDs, progress status, and date period.
     */
    private function getCount(array $companyIds, string $renewalProgress, string $period, \Closure $baseConditions): int
    {
        if (empty($companyIds)) {
            return 0;
        }

        $query = Renewal::query()
            ->where('hr_version', 1)
            ->whereIn('f_company_id', $companyIds)
            ->whereIn('renewal_progress', [$renewalProgress]);

        $baseConditions($query);

        match ($period) {
            'today' => $query->whereDate('follow_up_date', today()),
            'overdue' => $query->whereDate('follow_up_date', '<', today()),
            'upcoming' => $query->whereDate('follow_up_date', '>', today()),
            'all' => null, // no date filter
        };

        return $query->count();
    }
}
