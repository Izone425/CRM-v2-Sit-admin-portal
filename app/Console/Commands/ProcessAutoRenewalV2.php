<?php

namespace App\Console\Commands;

use App\Models\HrLicense;
use App\Models\HrSalesInvoice;
use App\Models\HrSalesInvoiceItem;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessAutoRenewalV2 extends Command
{
    protected $signature = 'renewal-hrv2:process-auto-renewal';

    protected $description = 'Check expiring HR V2 licenses with auto-renewal enabled, create sales invoices and send email notifications';

    public function handle(): int
    {
        $this->info('Starting HR V2 auto-renewal processing...');

        try {
            $today = now()->format('Y-m-d');

            // Find licenses expiring today with auto_renewal enabled
            $expiringLicenses = HrLicense::where('type', 'PAID')
                ->where('status', 'Enabled')
                ->where('auto_renewal', 'Enabled')
                ->where('end_date', $today)
                ->get();

            if ($expiringLicenses->isEmpty()) {
                $this->info('No auto-renewal licenses expiring today.');
                return Command::SUCCESS;
            }

            // Group by software_handover_id
            $grouped = $expiringLicenses->groupBy('software_handover_id');

            $invoiceCount = 0;
            $emailCount = 0;

            foreach ($grouped as $swId => $licenses) {
                try {
                    $handover = SoftwareHandover::find($swId);
                    if (!$handover) {
                        $this->warn("  Skipped - software handover {$swId} not found");
                        continue;
                    }

                    $companyName = $licenses->first()->company_name;
                    $this->info("Processing: {$companyName} (SW ID: {$swId})");

                    // Check if invoice already exists for today's renewal
                    $existsToday = HrSalesInvoice::where('software_handover_id', $swId)
                        ->where('sales_type', 'RENEWAL SALES')
                        ->whereDate('invoice_date', $today)
                        ->exists();

                    if ($existsToday) {
                        $this->info("  Skipped - renewal invoice already created today");
                        continue;
                    }

                    DB::beginTransaction();

                    // Create sales invoice
                    $invoiceNo = HrSalesInvoice::generateInvoiceNo();

                    $totalBeforeTax = 0;
                    $totalAfterTax = 0;
                    $items = [];

                    foreach ($licenses as $license) {
                        // Calculate renewal price from last invoice items
                        $lastInvoiceItem = HrSalesInvoiceItem::whereHas('salesInvoice', function ($q) use ($swId) {
                                $q->where('software_handover_id', $swId);
                            })
                            ->where('license_type', $license->license_type)
                            ->orderByDesc('id')
                            ->first();

                        $unitPrice = $lastInvoiceItem?->unit_price ?? 0;
                        $quantity = $license->unit ?? 1;
                        $subscriptionPeriod = $license->month ?? 12;
                        $itemBeforeTax = $unitPrice * $quantity * $subscriptionPeriod;
                        $taxAmount = $itemBeforeTax * 0.08; // 8% SST
                        $itemAfterTax = $itemBeforeTax + $taxAmount;

                        $newStartDate = Carbon::parse($license->end_date)->addDay();
                        $newEndDate = $newStartDate->copy()->addMonths($subscriptionPeriod)->subDay();

                        $items[] = [
                            'license_type' => $license->license_type,
                            'product_code' => $this->licenseTypeToProductCode($license->license_type),
                            'quantity' => $quantity,
                            'subscription_period' => $subscriptionPeriod,
                            'unit_price' => $unitPrice,
                            'taxation' => $taxAmount,
                            'tax_code' => 'SV-8',
                            'total_before_tax' => $itemBeforeTax,
                            'total_after_tax' => $itemAfterTax,
                            'license_start_date' => $newStartDate->format('Y-m-d'),
                            'license_end_date' => $newEndDate->format('Y-m-d'),
                        ];

                        $totalBeforeTax += $itemBeforeTax;
                        $totalAfterTax += $itemAfterTax;
                    }

                    $country = $handover->lead?->companyDetail?->country ?? null;

                    $salesInvoice = HrSalesInvoice::create([
                        'software_handover_id' => $swId,
                        'handover_id' => $handover->project_code ?? "SW_{$swId}",
                        'lead_id' => $handover->lead_id,
                        'invoice_no' => $invoiceNo,
                        'invoice_date' => now(),
                        'company_name' => $companyName,
                        'country' => $country,
                        'currency' => 'MYR',
                        'sales_type' => 'RENEWAL SALES',
                        'tax_rate' => 8,
                        'sales_amount' => $totalBeforeTax,
                        'invoice_amount' => $totalAfterTax,
                        'payment_status' => 'unpaid',
                        'auto_renewal' => 'Enabled',
                        'created_by_name' => 'System (Auto Renewal)',
                        'status' => 'pending',
                    ]);

                    // Create invoice items
                    foreach ($items as $index => $item) {
                        HrSalesInvoiceItem::create(array_merge($item, [
                            'hr_sales_invoice_id' => $salesInvoice->id,
                            'sort_order' => $index + 1,
                        ]));
                    }

                    DB::commit();
                    $invoiceCount++;

                    $this->info("  Created invoice: {$invoiceNo} (RM " . number_format($totalAfterTax, 2) . ")");

                    Log::info('HRV2 auto-renewal invoice created', [
                        'software_handover_id' => $swId,
                        'company_name' => $companyName,
                        'invoice_no' => $invoiceNo,
                        'amount' => $totalAfterTax,
                        'licenses' => $licenses->count(),
                    ]);

                    // Send email notification
                    $email = $handover->lead?->companyDetail?->email ?? $handover->lead?->email;
                    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $invoiceUrl = url('/admin/view-sales-invoice?invoiceId=' . $salesInvoice->id . '&softwareHandoverId=' . $swId);

                            Mail::send([], [], function (Message $message) use ($email, $companyName, $invoiceNo, $totalAfterTax, $items, $invoiceUrl) {
                                $itemsHtml = '';
                                foreach ($items as $item) {
                                    $itemsHtml .= '<tr>'
                                        . '<td style="padding:8px 12px;border:1px solid #e5e7eb;">' . $item['license_type'] . '</td>'
                                        . '<td style="padding:8px 12px;border:1px solid #e5e7eb;text-align:center;">' . $item['quantity'] . '</td>'
                                        . '<td style="padding:8px 12px;border:1px solid #e5e7eb;text-align:center;">' . $item['subscription_period'] . ' months</td>'
                                        . '<td style="padding:8px 12px;border:1px solid #e5e7eb;text-align:right;">RM ' . number_format($item['unit_price'], 2) . '</td>'
                                        . '<td style="padding:8px 12px;border:1px solid #e5e7eb;">' . $item['license_start_date'] . ' - ' . $item['license_end_date'] . '</td>'
                                        . '</tr>';
                                }

                                $body = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;">'
                                    . '<h2 style="color:#1a56db;">Auto Renewal Invoice</h2>'
                                    . '<p>Dear <strong>' . $companyName . '</strong>,</p>'
                                    . '<p>Your license subscription has been automatically renewed. A new invoice has been generated:</p>'
                                    . '<table style="width:100%;border-collapse:collapse;margin:16px 0;">'
                                    . '<tr><td style="padding:8px;font-weight:bold;width:140px;">Invoice No:</td><td>' . $invoiceNo . '</td></tr>'
                                    . '<tr><td style="padding:8px;font-weight:bold;">Amount:</td><td>RM ' . number_format($totalAfterTax, 2) . '</td></tr>'
                                    . '<tr><td style="padding:8px;font-weight:bold;">Date:</td><td>' . now()->format('d M Y') . '</td></tr>'
                                    . '</table>'
                                    . '<h3 style="margin-top:20px;">License Details</h3>'
                                    . '<table style="width:100%;border-collapse:collapse;margin:8px 0;">'
                                    . '<thead><tr style="background:#f3f4f6;">'
                                    . '<th style="padding:8px 12px;border:1px solid #e5e7eb;text-align:left;">Module</th>'
                                    . '<th style="padding:8px 12px;border:1px solid #e5e7eb;text-align:center;">Headcount</th>'
                                    . '<th style="padding:8px 12px;border:1px solid #e5e7eb;text-align:center;">Period</th>'
                                    . '<th style="padding:8px 12px;border:1px solid #e5e7eb;text-align:right;">Unit Price</th>'
                                    . '<th style="padding:8px 12px;border:1px solid #e5e7eb;">Dates</th>'
                                    . '</tr></thead>'
                                    . '<tbody>' . $itemsHtml . '</tbody>'
                                    . '</table>'
                                    . '<p style="margin-top:20px;">Please complete the payment to continue using the services.</p>'
                                    . '<p style="color:#6b7280;font-size:0.85rem;margin-top:24px;">This is an automated email from TimeTec CRM.</p>'
                                    . '</div>';

                                $message->to($email)
                                    ->subject('Auto Renewal Invoice - ' . $invoiceNo . ' - ' . $companyName)
                                    ->html($body);
                            });

                            $emailCount++;
                            $this->info("  Email sent to: {$email}");

                        } catch (\Exception $e) {
                            Log::warning('HRV2 auto-renewal email failed', [
                                'email' => $email,
                                'invoice_no' => $invoiceNo,
                                'error' => $e->getMessage(),
                            ]);
                            $this->warn("  Email failed: {$e->getMessage()}");
                        }
                    } else {
                        $this->warn("  No valid email found for notification");
                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("HRV2 auto-renewal error for SW {$swId}: " . $e->getMessage());
                    $this->error("  Error: {$e->getMessage()}");
                }
            }

            $this->info("Completed! {$invoiceCount} invoice(s) created, {$emailCount} email(s) sent.");

            Log::info('HRV2 auto-renewal processing completed', [
                'invoices_created' => $invoiceCount,
                'emails_sent' => $emailCount,
                'date' => $today,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Auto-renewal processing failed: {$e->getMessage()}");
            Log::error("HRV2 auto-renewal process error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function licenseTypeToProductCode(string $licenseType): string
    {
        return match ($licenseType) {
            'TimeTec Attendance' => 'TCL_TA USER-RENEWAL',
            'TimeTec Leave' => 'TCL_LEAVE USER-RENEWAL',
            'TimeTec Claim' => 'TCL_CLAIM USER-RENEWAL',
            'TimeTec Payroll' => 'TCL_PAYROLL USER-RENEWAL',
            default => $licenseType,
        };
    }
}
