<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ResellerDealerInformation extends Component
{
    public $search = '';
    public $sortField = 'f_company_name';
    public $sortDirection = 'asc';
    public $expandedDealer = null;
    public $expandedSubscriber = null;
    public $subscriberList = [];
    public $invoiceDetails = [];

    public function updatedSearch()
    {
        // Search updated
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleDealer($fId)
    {
        $fId = (int) $fId;

        if ($this->expandedDealer === $fId) {
            $this->expandedDealer = null;
            $this->subscriberList = [];
            $this->expandedSubscriber = null;
            $this->invoiceDetails = [];
        } else {
            $this->expandedDealer = $fId;
            $this->expandedSubscriber = null;
            $this->invoiceDetails = [];
            $this->loadSubscribers($fId);
        }
    }

    public function toggleSubscriber($fId)
    {
        $fId = (int) $fId;

        if ($this->expandedSubscriber === $fId) {
            $this->expandedSubscriber = null;
            $this->invoiceDetails = [];
        } else {
            $this->expandedSubscriber = $fId;
            $this->loadLicenseDetails($fId);
        }
    }

    public function loadSubscribers($dealerFId)
    {
        $subscribers = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', str_pad($dealerFId, 10, '0', STR_PAD_LEFT))
            ->get(['f_id', 'f_company_name', 'f_reg_date', 'f_type']);

        $result = [];
        foreach ($subscribers as $sub) {
            $activeLicenseCount = DB::connection('frontenddb')
                ->table('crm_company_license')
                ->where('f_company_id', $sub->f_id)
                ->where('f_type', 'PAID')
                ->where('status', 'Active')
                ->where(function($q) {
                    $q->where('f_name', 'like', '%TA%')
                      ->orWhere('f_name', 'like', '%leave%')
                      ->orWhere('f_name', 'like', '%claim%')
                      ->orWhere('f_name', 'like', '%payroll%')
                      ->orWhere('f_name', 'like', '%Face & QR Code%');
                })
                ->count();

            $earliestExpiry = DB::connection('frontenddb')
                ->table('crm_company_license')
                ->where('f_company_id', $sub->f_id)
                ->where('f_type', 'PAID')
                ->where('status', 'Active')
                ->whereDate('f_expiry_date', '>=', now()->format('Y-m-d'))
                ->where(function($q) {
                    $q->where('f_name', 'like', '%TA%')
                      ->orWhere('f_name', 'like', '%leave%')
                      ->orWhere('f_name', 'like', '%claim%')
                      ->orWhere('f_name', 'like', '%payroll%')
                      ->orWhere('f_name', 'like', '%Face & QR Code%');
                })
                ->min('f_expiry_date');

            // Determine renewal status
            $renewalStatus = 'pending';
            if ($earliestExpiry) {
                $ninetyDaysFromNow = now()->addDays(90);
                $newestLicense = DB::connection('frontenddb')
                    ->table('crm_company_license')
                    ->where('f_company_id', $sub->f_id)
                    ->where('f_type', 'PAID')
                    ->where('status', 'Active')
                    ->where('f_expiry_date', '>', $earliestExpiry)
                    ->where(function($q) {
                        $q->where('f_name', 'like', '%TA%')
                          ->orWhere('f_name', 'like', '%leave%')
                          ->orWhere('f_name', 'like', '%claim%')
                          ->orWhere('f_name', 'like', '%payroll%')
                          ->orWhere('f_name', 'like', '%Face & QR Code%');
                    })
                    ->orderBy('f_expiry_date', 'desc')
                    ->first(['f_expiry_date']);

                if ($newestLicense) {
                    $newestExpiry = Carbon::parse($newestLicense->f_expiry_date);
                    $renewalStatus = $newestExpiry->gt($ninetyDaysFromNow) ? 'done' : 'done_expiring';
                }
            }

            $result[] = [
                'f_id' => $sub->f_id,
                'f_company_name' => $sub->f_company_name,
                'f_reg_date' => $sub->f_reg_date,
                'f_type' => $sub->f_type,
                'active_license_count' => $activeLicenseCount,
                'earliest_expiry' => $earliestExpiry,
                'days_until_expiry' => $earliestExpiry ? now()->startOfDay()->diffInDays(Carbon::parse($earliestExpiry)->startOfDay(), false) : null,
                'renewal_status' => $renewalStatus,
            ];
        }

        // Also include the dealer itself as a subscriber (it may have its own licenses)
        $dealerLicenseCount = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->where('f_company_id', str_pad($dealerFId, 10, '0', STR_PAD_LEFT))
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where(function($q) {
                $q->where('f_name', 'like', '%TA%')
                  ->orWhere('f_name', 'like', '%leave%')
                  ->orWhere('f_name', 'like', '%claim%')
                  ->orWhere('f_name', 'like', '%payroll%')
                  ->orWhere('f_name', 'like', '%Face & QR Code%');
            })
            ->count();

        if ($dealerLicenseCount > 0) {
            $dealerInfo = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->where('f_id', str_pad($dealerFId, 10, '0', STR_PAD_LEFT))
                ->first(['f_company_name', 'f_reg_date']);

            $dealerExpiry = DB::connection('frontenddb')
                ->table('crm_company_license')
                ->where('f_company_id', str_pad($dealerFId, 10, '0', STR_PAD_LEFT))
                ->where('f_type', 'PAID')
                ->where('status', 'Active')
                ->whereDate('f_expiry_date', '>=', now()->format('Y-m-d'))
                ->min('f_expiry_date');

            $dealerRenewalStatus = 'pending';
            if ($dealerExpiry) {
                $ninetyDaysFromNow = now()->addDays(90);
                $dealerNewest = DB::connection('frontenddb')
                    ->table('crm_company_license')
                    ->where('f_company_id', str_pad($dealerFId, 10, '0', STR_PAD_LEFT))
                    ->where('f_type', 'PAID')
                    ->where('status', 'Active')
                    ->where('f_expiry_date', '>', $dealerExpiry)
                    ->where(function($q) {
                        $q->where('f_name', 'like', '%TA%')
                          ->orWhere('f_name', 'like', '%leave%')
                          ->orWhere('f_name', 'like', '%claim%')
                          ->orWhere('f_name', 'like', '%payroll%')
                          ->orWhere('f_name', 'like', '%Face & QR Code%');
                    })
                    ->orderBy('f_expiry_date', 'desc')
                    ->first(['f_expiry_date']);
                if ($dealerNewest) {
                    $dealerRenewalStatus = Carbon::parse($dealerNewest->f_expiry_date)->gt($ninetyDaysFromNow) ? 'done' : 'done_expiring';
                }
            }

            array_unshift($result, [
                'f_id' => $dealerFId,
                'f_company_name' => ($dealerInfo->f_company_name ?? 'Dealer') . ' (Self)',
                'f_reg_date' => $dealerInfo->f_reg_date ?? null,
                'f_type' => 'DEALER',
                'active_license_count' => $dealerLicenseCount,
                'earliest_expiry' => $dealerExpiry,
                'days_until_expiry' => $dealerExpiry ? now()->startOfDay()->diffInDays(Carbon::parse($dealerExpiry)->startOfDay(), false) : null,
                'renewal_status' => $dealerRenewalStatus,
            ]);
        }

        // Sort: subscribers with licenses first (by earliest expiry), no licenses at bottom
        usort($result, function ($a, $b) {
            if ($a['earliest_expiry'] && !$b['earliest_expiry']) return -1;
            if (!$a['earliest_expiry'] && $b['earliest_expiry']) return 1;
            if (!$a['earliest_expiry'] && !$b['earliest_expiry']) return strcasecmp($a['f_company_name'], $b['f_company_name']);
            return strtotime($a['earliest_expiry']) - strtotime($b['earliest_expiry']);
        });

        $this->subscriberList = $result;
    }

    public function loadLicenseDetails($fId)
    {
        $licenses = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->where('f_company_id', str_pad((int) $fId, 10, '0', STR_PAD_LEFT))
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where(function($q) {
                $q->where('f_name', 'like', '%TA%')
                  ->orWhere('f_name', 'like', '%leave%')
                  ->orWhere('f_name', 'like', '%claim%')
                  ->orWhere('f_name', 'like', '%payroll%')
                  ->orWhere('f_name', 'like', '%Face & QR Code%');
            })
            ->orderBy('f_expiry_date', 'asc')
            ->get(['f_name', 'f_total_user', 'f_start_date', 'f_expiry_date', 'f_invoice_no', 'f_billing_cycle', 'status']);

        $invoiceGroups = [];
        $licenseSummary = ['attendance' => 0, 'leave' => 0, 'claim' => 0, 'payroll' => 0];

        foreach ($licenses as $license) {
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';
            $quantity = $license->f_total_user;

            if (strpos($license->f_name, 'TimeTec TA') !== false) $licenseSummary['attendance'] += $quantity;
            if (strpos($license->f_name, 'TimeTec Leave') !== false) $licenseSummary['leave'] += $quantity;
            if (strpos($license->f_name, 'TimeTec Claim') !== false) $licenseSummary['claim'] += $quantity;
            if (strpos($license->f_name, 'TimeTec Payroll') !== false) $licenseSummary['payroll'] += $quantity;

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = ['products' => []];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_total_user' => $quantity,
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $license->f_billing_cycle ?? 0,
                'status' => $license->status ?? 'Active',
            ];
        }

        $this->invoiceDetails = $invoiceGroups;
        $this->invoiceDetails['_summary'] = $licenseSummary;
    }

    public function getDealersProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        // Get all companies under this reseller
        $allLinks = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $reseller->reseller_id)
            ->get(['f_id', 'f_company_name', 'f_reg_date', 'f_rate', 'f_type']);

        $companies = [];

        foreach ($allLinks as $link) {
            // Check if this company has sub-customers
            $subscriberCount = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->where('reseller_id', $link->f_id)
                ->count();

            // Only show companies that have subscribers under them
            if ($subscriberCount === 0) {
                continue;
            }

            if ($this->search && stripos($link->f_company_name, $this->search) === false) {
                continue;
            }

            $companies[] = (object) [
                'f_id' => $link->f_id,
                'f_company_name' => $link->f_company_name,
                'f_reg_date' => $link->f_reg_date,
                'f_rate' => $link->f_rate,
                'f_type' => $link->f_type,
                'subscriber_count' => $subscriberCount,
            ];
        }

        usort($companies, function($a, $b) {
            $comparison = match ($this->sortField) {
                'f_company_name' => strcasecmp($a->f_company_name, $b->f_company_name),
                'f_reg_date' => strtotime($a->f_reg_date ?? '0') - strtotime($b->f_reg_date ?? '0'),
                'subscriber_count' => $a->subscriber_count - $b->subscriber_count,
                default => 0,
            };
            return $this->sortDirection === 'asc' ? $comparison : -$comparison;
        });

        return collect($companies);
    }

    public function getDealerCountProperty()
    {
        $reseller = Auth::guard('reseller')->user();
        if (!$reseller || !$reseller->reseller_id) return 0;

        $allLinks = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $reseller->reseller_id)
            ->pluck('f_id');

        if ($allLinks->isEmpty()) return 0;

        return DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->whereIn('reseller_id', $allLinks)
            ->distinct('reseller_id')
            ->count('reseller_id');
    }

    public function render()
    {
        return view('livewire.reseller-dealer-information', [
            'dealers' => $this->dealers,
            'dealerCount' => $this->dealerCount,
        ]);
    }
}
