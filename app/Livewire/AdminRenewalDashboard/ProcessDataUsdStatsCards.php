<?php

namespace App\Livewire\AdminRenewalDashboard;

use App\Filament\Pages\RenewalDataUsd;
use App\Models\Renewal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ProcessDataUsdStatsCards extends Component
{
    public $newStats;
    public $pendingConfirmationStats;
    public $renewalForecastStats;
    public $pendingPaymentStats;
    public $renewalForecastCurrentMonthStats;

    public ?array $companyIds = null;

    public static function placeholder(array $params = [])
    {
        return view('components.renewal-myr-cards-skeleton');
    }

    public function mount(?array $companyIds = null): void
    {
        $this->companyIds = $companyIds;
        $this->loadData();
    }

    public function refreshStats(): void
    {
        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->newStats = $this->getStatsForProgress(['new']);
        $this->pendingConfirmationStats = $this->getStatsForProgress(['pending_confirmation']);
        $this->pendingPaymentStats = $this->getStatsForProgress(['pending_payment']);
        $this->renewalForecastStats = [
            'total_companies' => $this->newStats['total_companies'] + $this->pendingConfirmationStats['total_companies'],
            'total_invoices' => $this->newStats['total_invoices'] + $this->pendingConfirmationStats['total_invoices'],
            'total_amount' => $this->newStats['total_amount'] + $this->pendingConfirmationStats['total_amount'],
            'total_via_reseller' => $this->newStats['total_via_reseller'] + $this->pendingConfirmationStats['total_via_reseller'],
            'total_via_end_user' => $this->newStats['total_via_end_user'] + $this->pendingConfirmationStats['total_via_end_user'],
            'total_via_reseller_amount' => $this->newStats['total_via_reseller_amount'] + $this->pendingConfirmationStats['total_via_reseller_amount'],
            'total_via_end_user_amount' => $this->newStats['total_via_end_user_amount'] + $this->pendingConfirmationStats['total_via_end_user_amount'],
        ];
        $this->renewalForecastCurrentMonthStats = $this->getRenewalForecastCurrentMonthStats();
    }

    protected function getStatsForProgress(array $progressValues): array
    {
        try {
            if ($this->companyIds === null) {
                $renewals = collect();
            } elseif (empty($this->companyIds)) {
                $renewals = collect();
            } else {
                $renewals = Renewal::whereIn('renewal_progress', $progressValues)
                    ->whereIn('f_company_id', $this->companyIds)
                    ->with(['lead.quotations.items'])
                    ->get();
            }

            return $this->computeStats($renewals);
        } catch (\Exception $e) {
            Log::error('Error fetching USD renewal stats for '.implode(',', $progressValues).': '.$e->getMessage());
            return $this->emptyStats();
        }
    }

    protected function getRenewalForecastCurrentMonthStats(): array
    {
        try {
            $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');
            $today = Carbon::now()->format('Y-m-d');

            $renewalsQuery = Renewal::whereIn('renewal_progress', ['new', 'pending_confirmation'])
                ->with(['lead.quotations.items']);

            if (is_array($this->companyIds)) {
                if (empty($this->companyIds)) {
                    return $this->computeStats(collect());
                }
                $renewalsQuery->whereIn('f_company_id', $this->companyIds);
            }

            $renewals = $renewalsQuery->get()
                ->filter(function ($renewal) use ($startOfMonth, $endOfMonth, $today) {
                    return RenewalDataUsd::where('f_company_id', $renewal->f_company_id)
                        ->whereBetween('f_expiry_date', [$startOfMonth, $endOfMonth])
                        ->where('f_expiry_date', '>=', $today)
                        ->where('f_currency', 'USD')
                        ->exists();
                });

            return $this->computeStats($renewals);
        } catch (\Exception $e) {
            Log::error('Error fetching USD renewal forecast current month stats: ' . $e->getMessage());
            return $this->emptyStats();
        }
    }

    protected function computeStats($renewals): array
    {
        $totalCompanies = $renewals->count();
        $totalInvoices = 0;
        $totalAmount = 0;
        $totalViaResellerCount = 0;
        $totalViaEndUserCount = 0;
        $totalViaResellerAmount = 0;
        $totalViaEndUserAmount = 0;

        foreach ($renewals as $renewal) {
            if ($renewal->lead_id && $renewal->lead) {
                $renewalQuotations = $renewal->lead->quotations()
                    ->where('mark_as_final', true)
                    ->where('sales_type', 'RENEWAL SALES')
                    ->get();

                if ($renewalQuotations->isNotEmpty()) {
                    $totalInvoices += $renewalQuotations->count();
                    $quotationAmount = 0;
                    foreach ($renewalQuotations as $quotation) {
                        $quotationAmount += $quotation->items->sum('total_before_tax');
                    }
                    $totalAmount += $quotationAmount;

                    $reseller = RenewalDataUsd::getResellerForCompany($renewal->f_company_id);
                    if ($reseller && $reseller->f_rate) {
                        $totalViaResellerAmount += $quotationAmount;
                    } else {
                        $totalViaEndUserAmount += $quotationAmount;
                    }
                }
            }

            $reseller = RenewalDataUsd::getResellerForCompany($renewal->f_company_id);
            if ($reseller && $reseller->f_rate) {
                $totalViaResellerCount++;
            } else {
                $totalViaEndUserCount++;
            }
        }

        return [
            'total_companies' => $totalCompanies,
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'total_via_reseller' => $totalViaResellerCount,
            'total_via_end_user' => $totalViaEndUserCount,
            'total_via_reseller_amount' => $totalViaResellerAmount,
            'total_via_end_user_amount' => $totalViaEndUserAmount,
        ];
    }

    protected function emptyStats(): array
    {
        return [
            'total_companies' => 0,
            'total_invoices' => 0,
            'total_amount' => 0,
            'total_via_reseller' => 0,
            'total_via_end_user' => 0,
            'total_via_reseller_amount' => 0,
            'total_via_end_user_amount' => 0,
        ];
    }

    public function render()
    {
        return view('livewire.admin-renewal-dashboard.process-data-usd-stats-cards');
    }
}
