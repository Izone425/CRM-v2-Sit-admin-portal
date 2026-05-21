<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\HrLicense;
use App\Models\HrSalesInvoice;
use App\Models\SoftwareHandover;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;

class ARLicenseV2Tabs
{
    public static function getSchema(): array
    {
        return [
            Placeholder::make('license_v2_summary')
                ->label('')
                ->content(function ($record) {
                    if (!$record || !$record->id) {
                        return new HtmlString('<p>No license data available</p>');
                    }

                    return self::getLicenseTable($record->id);
                })
        ];
    }

    private static function getLicenseTable($leadId): HtmlString
    {
        $handover = SoftwareHandover::where('lead_id', $leadId)->first();

        if (!$handover) {
            return new HtmlString('<p style="color:#6b7280; padding:16px;">No HR V2 handover linked to this lead.</p>');
        }

        $licenseData = self::getLicenseData($handover->id);
        $invoiceDetails = self::getInvoiceDetails($handover->id);

        $html = '
        <div class="license-summary-container">
            <style>
                .license-summary-container {
                    margin: 16px 0;
                }

                .license-summary-table table,
                .invoice-details-table table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 16px 0;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }

                .license-summary-table th,
                .license-summary-table td,
                .invoice-details-table th,
                .invoice-details-table td {
                    padding: 12px 8px;
                    text-align: center;
                    border: 1px solid #e5e7eb;
                    vertical-align: middle;
                }

                .license-summary-table th {
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }

                .license-summary-table td {
                    font-size: 18px;
                    font-weight: 600;
                    color: #1f2937;
                }

                .invoice-details-table th {
                    background-color: #f9fafb !important;
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }

                .invoice-details-table td {
                    font-size: 13px;
                    color: #1f2937;
                }

                .module-col {
                    width: 18.75% !important;
                    text-align: center !important;
                    padding-left: 12px !important;
                }

                .headcount-col {
                    width: 6.25% !important;
                    text-align: center !important;
                    font-weight: bold !important;
                }

                .attendance-module { background-color: rgba(34, 197, 94, 0.1) !important; color: rgba(34, 197, 94, 1) !important; }
                .attendance-count { background-color: rgba(34, 197, 94, 1) !important; color: white !important; }
                .leave-module { background-color: rgba(37, 99, 235, 0.1) !important; color: rgba(37, 99, 235, 1) !important; }
                .leave-count { background-color: rgba(37, 99, 235, 1) !important; color: white !important; }
                .claim-module { background-color: rgba(124, 58, 237, 0.1) !important; color: rgba(124, 58, 237, 1) !important; }
                .claim-count { background-color: rgba(124, 58, 237, 1) !important; color: white !important; }
                .payroll-module { background-color: rgba(249, 115, 22, 0.1) !important; color: rgba(249, 115, 22, 1) !important; }
                .payroll-count { background-color: rgba(249, 115, 22, 1) !important; color: white !important; }

                .invoice-header {
                    background-color: #f3f4f6 !important;
                    font-weight: 700;
                    color: #1f2937;
                    font-size: 15px;
                }

                .invoice-group { margin-bottom: 24px; }

                .invoice-title {
                    background-color: #e5e7eb;
                    padding: 8px 12px;
                    font-weight: 600;
                    color: #374151;
                    border-radius: 4px;
                    margin-bottom: 8px;
                }

                .invoice-link {
                    color: #2563eb;
                    text-decoration: none;
                    font-weight: 600;
                }
                .invoice-link:hover {
                    color: #1d4ed8;
                    text-decoration: underline;
                }

                .product-row-ta { background-color: rgba(34, 197, 94, 0.1) !important; }
                .product-row-leave { background-color: rgba(37, 99, 235, 0.1) !important; }
                .product-row-claim { background-color: rgba(124, 58, 237, 0.1) !important; }
                .product-row-payroll { background-color: rgba(249, 115, 22, 0.1) !important; }

                .text-right { text-align: right; }
                .text-left { text-align: left; }
            </style>

            <!-- License Summary Table -->
            <div class="license-summary-table">
                <table>
                    <thead>
                        <tr>
                            <th class="module-col attendance-module">ATTENDANCE</th>
                            <th class="headcount-col attendance-count">' . $licenseData['attendance'] . '</th>
                            <th class="module-col leave-module">LEAVE</th>
                            <th class="headcount-col leave-count">' . $licenseData['leave'] . '</th>
                            <th class="module-col claim-module">CLAIM</th>
                            <th class="headcount-col claim-count">' . $licenseData['claim'] . '</th>
                            <th class="module-col payroll-module">PAYROLL</th>
                            <th class="headcount-col payroll-count">' . $licenseData['payroll'] . '</th>
                        </tr>
                    </thead>
                </table>
            </div>';

        // Invoice Details Tables
        if (!empty($invoiceDetails)) {
            $html .= '<div class="invoice-details-container">';

            foreach ($invoiceDetails as $invoiceNumber => $invoiceData) {
                $invoiceLink = url('/admin/view-sales-invoice') . '?invoiceId=' . $invoiceData['invoice_id'] . '&softwareHandoverId=' . $handover->id . '&from=invoice';

                $html .= '
                <div class="invoice-group">
                    <div class="invoice-title">Invoice: <a href="' . $invoiceLink . '" target="_blank" class="invoice-link">' . htmlspecialchars($invoiceNumber) . '</a></div>
                    <div class="invoice-details-table">
                        <table>
                            <thead>
                                <tr class="invoice-header">
                                    <th class="text-left">Product Name</th>
                                    <th>Qty</th>
                                    <th class="text-right">Price (RM)</th>
                                    <th>Billing Cycle</th>
                                    <th>Start Date</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>';

                foreach ($invoiceData['products'] as $product) {
                    $productType = self::getProductType($product['license_type']);
                    $html .= '
                                <tr class="product-row-' . $productType . '">
                                    <td style="text-align: left;">' . htmlspecialchars($product['license_type']) . '</td>
                                    <td>' . $product['quantity'] . '</td>
                                    <td class="text-right">' . number_format($product['unit_price'], 2) . '</td>
                                    <td>' . ($product['subscription_period'] ?? 12) . ' months</td>
                                    <td>' . ($product['start_date'] ? date('d M Y', strtotime($product['start_date'])) : '-') . '</td>
                                    <td>' . ($product['end_date'] ? date('d M Y', strtotime($product['end_date'])) : '-') . '</td>
                                </tr>';
                }

                $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    private static function getLicenseData($softwareHandoverId): array
    {
        $licenses = HrLicense::where('software_handover_id', $softwareHandoverId)
            ->where('type', 'PAID')
            ->where('status', 'Enabled')
            ->whereDate('end_date', '>=', today())
            ->get();

        $totals = [
            'attendance' => 0,
            'leave' => 0,
            'claim' => 0,
            'payroll' => 0,
        ];

        foreach ($licenses as $license) {
            $type = strtolower($license->license_type ?? '');

            if (str_contains($type, 'attendance')) {
                $totals['attendance'] += $license->unit;
            } elseif (str_contains($type, 'leave')) {
                $totals['leave'] += $license->unit;
            } elseif (str_contains($type, 'claim')) {
                $totals['claim'] += $license->unit;
            } elseif (str_contains($type, 'payroll')) {
                $totals['payroll'] += $license->unit;
            }
        }

        return $totals;
    }

    private static function getInvoiceDetails($softwareHandoverId): array
    {
        $invoices = HrSalesInvoice::where('software_handover_id', $softwareHandoverId)
            ->orderBy('invoice_date', 'desc')
            ->get();

        $invoiceGroups = [];

        foreach ($invoices as $invoice) {
            $invoiceNo = $invoice->invoice_no ?? 'No Invoice';

            $items = DB::table('hr_sales_invoice_items')
                ->where('hr_sales_invoice_id', $invoice->id)
                ->orderBy('sort_order')
                ->get();

            $invoiceGroups[$invoiceNo] = [
                'invoice_id' => $invoice->id,
                'products' => [],
                'total_amount' => $invoice->invoice_amount ?? 0,
            ];

            foreach ($items as $item) {
                $invoiceGroups[$invoiceNo]['products'][] = [
                    'license_type' => $item->license_type ?? $item->product_code ?? '',
                    'quantity' => $item->quantity ?? 0,
                    'unit_price' => $item->unit_price ?? 0,
                    'subscription_period' => $item->subscription_period ?? 12,
                    'start_date' => $item->license_start_date,
                    'end_date' => $item->license_end_date,
                ];
            }
        }

        return $invoiceGroups;
    }

    private static function getProductType($productName): string
    {
        $name = strtolower($productName);

        if (str_contains($name, 'attendance') || str_contains($name, 'timetec ta') || str_contains($name, 'tcl_ta')) {
            return 'ta';
        } elseif (str_contains($name, 'leave') || str_contains($name, 'tcl_leave')) {
            return 'leave';
        } elseif (str_contains($name, 'claim') || str_contains($name, 'tcl_claim')) {
            return 'claim';
        } elseif (str_contains($name, 'payroll') || str_contains($name, 'tcl_payroll')) {
            return 'payroll';
        }

        return 'ta';
    }
}
