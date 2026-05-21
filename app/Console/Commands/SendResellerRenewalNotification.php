<?php

namespace App\Console\Commands;

use App\Mail\ResellerRenewalExpiryNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendResellerRenewalNotification extends Command
{
    protected $signature = 'reseller:send-renewal-notification
                            {--to= : Override recipient. When set, every email is redirected to this address instead of the reseller email (test mode).}
                            {--reseller-id= : Optional. Scope to a single reseller_v2.id (test mode). Leave empty to send to ALL active resellers.}';
    protected $description = 'Send weekly renewal expiry notification to resellers with licenses expiring within 90 days';

    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $ninetyDaysFromNow = Carbon::now()->startOfDay()->addDays(90);

        // Test-mode override — when --to is supplied every email goes to this address only.
        $overrideTo = $this->option('to') ?: null;
        if ($overrideTo) {
            $this->warn("TEST MODE: every email will be redirected to {$overrideTo} instead of the real reseller.");
        }

        $onlyResellerId = $this->option('reseller-id') ?: null;
        if ($onlyResellerId) {
            $this->warn("TEST MODE: scoped to reseller_v2.id = {$onlyResellerId}.");
        }

        // Get active resellers from reseller_v2 (optionally scoped to one id for testing)
        $resellers = DB::table('reseller_v2')
            ->where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->when($onlyResellerId, fn ($q) => $q->where('id', $onlyResellerId))
            ->get();

        $totalSent = 0;

        foreach ($resellers as $reseller) {
            // The reseller_renewal_reminders table records OPT-OUTS keyed by
            // (reseller_id, f_company_id, f_invoice_no) — exclude those invoices below.
            $excludedInvoices = \App\Models\ResellerRenewalReminder::where('reseller_id', $reseller->reseller_id)
                ->get(['f_company_id', 'f_invoice_no'])
                ->groupBy('f_company_id')
                ->map(fn ($rows) => $rows->pluck('f_invoice_no')->map(fn ($v) => (string) $v)->all())
                ->all();

            $resellerLinks = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->where('reseller_id', $reseller->reseller_id)
                ->get(['f_id', 'f_company_name']);

            if ($resellerLinks->isEmpty()) {
                continue;
            }

            $companiesWithExpiry = [];

            foreach ($resellerLinks as $link) {
                // Get earliest expiring PAID Active license. The reseller already curated this
                // company onto their reminder list, so we no longer cap to a 90-day window.
                $expiringLicense = DB::connection('frontenddb')
                    ->table('crm_company_license')
                    ->where('f_company_id', $link->f_id)
                    ->where('f_type', 'PAID')
                    ->where('status', 'Active')
                    ->where(function($q) {
                        $q->where('f_name', 'like', '%TA%')
                          ->orWhere('f_name', 'like', '%leave%')
                          ->orWhere('f_name', 'like', '%claim%')
                          ->orWhere('f_name', 'like', '%payroll%')
                          ->orWhere('f_name', 'like', '%Face & QR Code%');
                    })
                    ->whereBetween('f_expiry_date', [$today->format('Y-m-d'), $ninetyDaysFromNow->format('Y-m-d')])
                    ->orderBy('f_expiry_date', 'asc')
                    ->first(['f_expiry_date']);

                if (!$expiringLicense) {
                    continue;
                }

                $expiryDate = Carbon::parse($expiringLicense->f_expiry_date);

                // Latest active license expiry for this company — used to decide
                // per-invoice renewal status (any invoice whose expiry is < this
                // value is considered renewed by a later license).
                $latestExpiryRaw = DB::connection('frontenddb')
                    ->table('crm_company_license')
                    ->where('f_company_id', $link->f_id)
                    ->where('f_type', 'PAID')
                    ->where('status', 'Active')
                    ->where(function($q) {
                        $q->where('f_name', 'like', '%TA%')
                          ->orWhere('f_name', 'like', '%leave%')
                          ->orWhere('f_name', 'like', '%claim%')
                          ->orWhere('f_name', 'like', '%payroll%')
                          ->orWhere('f_name', 'like', '%Face & QR Code%');
                    })
                    ->max('f_expiry_date');

                // Per-invoice breakdown: distinct (invoice_no, expiry_date) for active licenses
                // for this company, expiring on or after today. Renewal status is computed
                // per-invoice — an invoice is "renewed" when a license with a later expiry
                // exists for the same company.
                $companyExcludedInvoices = $excludedInvoices[(int) $link->f_id] ?? [];

                $allInvoiceRows = DB::connection('frontenddb')
                    ->table('crm_company_license')
                    ->where('f_company_id', $link->f_id)
                    ->where('f_type', 'PAID')
                    ->where('status', 'Active')
                    ->where(function($q) {
                        $q->where('f_name', 'like', '%TA%')
                          ->orWhere('f_name', 'like', '%leave%')
                          ->orWhere('f_name', 'like', '%claim%')
                          ->orWhere('f_name', 'like', '%payroll%')
                          ->orWhere('f_name', 'like', '%Face & QR Code%');
                    })
                    ->whereBetween('f_expiry_date', [$today->format('Y-m-d'), $ninetyDaysFromNow->format('Y-m-d')])
                    ->select('f_invoice_no', 'f_expiry_date')
                    ->distinct()
                    ->orderBy('f_expiry_date', 'asc')
                    ->get();

                // Only count done marks for invoices that are currently active — prevents stale
                // marks from old/expired invoices inflating the counter against current invoices.
                $activeInvoiceNosForCompany = DB::connection('frontenddb')
                    ->table('crm_company_license')
                    ->where('f_company_id', $link->f_id)
                    ->where('f_type', 'PAID')
                    ->where('status', 'Active')
                    ->where(function($q) {
                        $q->where('f_name', 'like', '%TA%')
                          ->orWhere('f_name', 'like', '%leave%')
                          ->orWhere('f_name', 'like', '%claim%')
                          ->orWhere('f_name', 'like', '%payroll%')
                          ->orWhere('f_name', 'like', '%Face & QR Code%');
                    })
                    ->whereDate('f_expiry_date', '>=', $today->format('Y-m-d'))
                    ->whereNotNull('f_invoice_no')
                    ->distinct()
                    ->pluck('f_invoice_no')
                    ->map(fn($v) => (string) $v)
                    ->toArray();

                $totalInvoiceCount = count($activeInvoiceNosForCompany);
                $doneInvoiceCount  = count(array_intersect(array_filter($companyExcludedInvoices), $activeInvoiceNosForCompany));

                $invoiceRows = $allInvoiceRows
                    ->reject(fn ($row) => in_array((string) ($row->f_invoice_no ?: 'No Invoice'), $companyExcludedInvoices, true))
                    ->map(function ($row) use ($latestExpiryRaw, $ninetyDaysFromNow) {
                        $invoiceRenewal = 'pending';
                        if ($latestExpiryRaw && $latestExpiryRaw > $row->f_expiry_date) {
                            $latest = Carbon::parse($latestExpiryRaw);
                            $invoiceRenewal = $latest->gt($ninetyDaysFromNow) ? 'done' : 'done_expiring';
                        }
                        return [
                            'invoice_no' => $row->f_invoice_no ?: 'No Invoice',
                            'expiry_date' => $row->f_expiry_date,
                            'renewal_status' => $invoiceRenewal,
                        ];
                    })
                    ->values()
                    ->all();

                if (empty($invoiceRows)) {
                    continue;
                }

                $earliestPendingExpiry = Carbon::parse($invoiceRows[0]['expiry_date']);
                // Use the overall earliest expiry for sorting so email order matches the dashboard.
                $overallEarliestExpiry = Carbon::parse($expiringLicense->f_expiry_date);

                $overallDays = (int) $today->diffInDays($overallEarliestExpiry);

                $companiesWithExpiry[] = [
                    'company_name'        => $link->f_company_name,
                    'expiry_date'         => $expiringLicense->f_expiry_date,
                    'days_remaining'      => $overallDays,
                    'sort_days'           => $overallDays,
                    'invoices'            => $invoiceRows,
                    'total_invoice_count' => $totalInvoiceCount,
                    'done_invoice_count'  => $doneInvoiceCount,
                ];
            }

            if (empty($companiesWithExpiry)) {
                continue;
            }

            // Sort by overall earliest expiry ascending — matches dashboard order.
            usort($companiesWithExpiry, fn($a, $b) => $a['sort_days'] - $b['sort_days']);

            try {
                $toAddress = $overrideTo ?: $reseller->email;

                Mail::to($toAddress)
                    ->bcc([
                        'faiz@timeteccloud.com',
                        'fatimah.tarmizi@timeteccloud.com',
                    ])
                    ->send(new ResellerRenewalExpiryNotification(
                        $reseller->company_name,
                        $companiesWithExpiry
                    ));

                $totalSent++;

                Log::info("Reseller renewal notification sent", [
                    'reseller' => $reseller->company_name,
                    'email' => $toAddress,
                    'redirected' => (bool) $overrideTo,
                    'companies_count' => count($companiesWithExpiry),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send reseller renewal notification", [
                    'reseller' => $reseller->company_name,
                    'email' => $reseller->email,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Failed: {$reseller->company_name} - {$e->getMessage()}");
            }
        }

        $this->info("Reseller renewal notifications sent: {$totalSent}");
        Log::info("Reseller renewal notification job completed. Total sent: {$totalSent}");
    }
}
