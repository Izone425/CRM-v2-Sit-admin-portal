<?php

namespace App\Models;

use App\Models\Lead;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\SoftwareHandover;
use App\Models\HrSalesInvoiceItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class HrSalesInvoice extends Model
{
    use HasFactory;

    protected $table = 'hr_sales_invoices';

    protected $fillable = [
        'software_handover_id',
        'handover_id',
        'quotation_id',
        'lead_id',
        'invoice_no',
        'invoice_date',
        'company_name',
        'country',
        'pi_no',
        'quotation_reference_no',
        'headcount',
        'currency',
        'sales_type',
        'subscription_period',
        'tax_rate',
        'sales_amount',
        'invoice_amount',
        'payment_method',
        'payment_status',
        'auto_renewal',
        'created_by_name',
        'status',
        'cancel_remark',
        'reseller',
        'commission',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'sales_amount' => 'decimal:2',
        'invoice_amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'tax_rate' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(HrSalesInvoiceItem::class, 'hr_sales_invoice_id');
    }

    public function softwareHandover()
    {
        return $this->belongsTo(SoftwareHandover::class);
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Copy quotation data to hr_sales_invoice and hr_sales_invoice_items
     * Only copies products where solution is software-related
     */
    public static function createHrSalesInvoiceFromQuotationsforSH(
        SoftwareHandover $record,
        array $allPiIds,
        string $handoverId,
        string $companyName,
        $bufferStartDate,
        $bufferEndDate,
    ): ?string {
        try {
            if (empty($allPiIds)) {
                return null;
            }

            $softwareSolutions = ['software', 'software_new_sales', 'software_renewal_sales'];

            $quotations = Quotation::whereIn('id', $allPiIds)->get();

            foreach ($quotations as $quotation) {
                // Skip if invoice already exists for this handover + quotation
                if (static::where('software_handover_id', $record->id)->where('quotation_id', $quotation->id)->exists()) {
                    Log::info("HR Sales Invoice already exists, skipping", [
                        'handover_id' => $handoverId,
                        'quotation_id' => $quotation->id,
                    ]);
                    continue;
                }
                $details = QuotationDetail::where('quotation_id', $quotation->id)
                    ->with('product')
                    ->orderBy('sort_order')
                    ->get();

                // Filter to only software-related products
                $softwareDetails = $details->filter(function ($detail) use ($softwareSolutions) {
                    return $detail->product && in_array($detail->product->solution, $softwareSolutions);
                });

                if ($softwareDetails->isEmpty()) {
                    continue;
                }

                $totalBeforeTax = $softwareDetails->sum('total_before_tax');
                $totalAfterTax = $softwareDetails->sum('total_after_tax');
                $invoiceNo = static::generateInvoiceNo();

                $salesInvoice = static::create([
                    'software_handover_id' => $record->id,
                    'handover_id' => $handoverId,
                    'quotation_id' => $quotation->id,
                    'lead_id' => $record->lead_id,
                    'invoice_no' => $invoiceNo,
                    'invoice_date' => now(),
                    'company_name' => $companyName,
                    'pi_no' => $quotation->pi_reference_no ?? null,
                    'quotation_reference_no' => $quotation->quotation_reference_no ?? null,
                    'headcount' => $quotation->headcount ?? $record->headcount ?? null,
                    'currency' => $quotation->currency ?? 'MYR',
                    'sales_type' => $quotation->sales_type ?? null,
                    'subscription_period' => $quotation->subscription_period ?? null,
                    'tax_rate' => $quotation->tax_rate ?? 0,
                    'sales_amount' => $totalBeforeTax,
                    'invoice_amount' => $totalAfterTax,
                    'payment_method' => null,
                    'payment_status' => 'unpaid',
                    'auto_renewal' => 'Disabled',
                    'created_by_name' => auth()->user()->name ?? 'System',
                    'status' => 'pending',
                ]);

                // Copy each software detail as invoice item
                foreach ($softwareDetails as $detail) {
                    $productCode = $detail->product->code ?? '';
                    $licenseType = match (true) {
                        str_contains(strtoupper($productCode), 'TCL_TA') => 'TimeTec TA',
                        str_contains(strtoupper($productCode), 'TCL_LEAVE') => 'TimeTec Leave',
                        str_contains(strtoupper($productCode), 'TCL_CLAIM') => 'TimeTec Claim',
                        str_contains(strtoupper($productCode), 'TCL_PAYROLL') => 'TimeTec Payroll',
                        str_contains(strtoupper($productCode), 'TCL_HIRE') => 'TimeTec Hire',
                        str_contains(strtoupper($productCode), 'TCL_PROFILE') => 'TimeTec Profile',
                        str_contains(strtoupper($productCode), 'TCL_ACCESS') => 'TimeTec Access',
                        str_contains(strtoupper($productCode), 'TCL_APPRAISAL') => 'TimeTec Appraisal',
                        str_contains(strtoupper($productCode), 'TCL_FULL') => 'TimeTec Full',
                        str_contains(strtoupper($productCode), 'TCL_RENEWAL') => 'TimeTec Renewal',
                        str_contains(strtoupper($productCode), 'TCL_POWER BI') => 'TimeTec Power BI',
                        str_contains(strtoupper($productCode), 'TIMETEC-TA') => 'TimeTec TA',
                        str_contains(strtoupper($productCode), 'TIMETEC-TL') => 'TimeTec Leave',
                        str_contains(strtoupper($productCode), 'TIMETEC-TC') => 'TimeTec Claim',
                        str_contains(strtoupper($productCode), 'TIMETEC-TP') => 'TimeTec Payroll',
                        default => $productCode,
                    };

                    HrSalesInvoiceItem::create([
                        'hr_sales_invoice_id' => $salesInvoice->id,
                        'product_id' => $detail->product_id,
                        'product_code' => $productCode,
                        'description' => $detail->description,
                        'license_type' => $licenseType,
                        'quantity' => $detail->quantity ?? 0,
                        'subscription_period' => $detail->subscription_period ?? 12,
                        'license_start_date' => $detail->license_start_date ?? now(),
                        'license_end_date' => $detail->license_end_date ?? now()->addMonths($detail->subscription_period ?? 12)->subDay(),
                        'unit_price' => $detail->unit_price ?? 0,
                        'discount' => $detail->discount ?? 0,
                        'taxation' => $detail->taxation ?? 0,
                        'tax_code' => $detail->tax_code ?? null,
                        'year' => $detail->year ?? null,
                        'tariff_code' => $detail->tariff_code ?? null,
                        'total_before_tax' => $detail->total_before_tax ?? 0,
                        'total_after_tax' => $detail->total_after_tax ?? 0,
                        'sort_order' => $detail->sort_order ?? 0,
                    ]);
                }

                Log::info("Created hr_sales_invoice from quotation", [
                    'handover_id' => $handoverId,
                    'quotation_id' => $quotation->id,
                    'sales_invoice_id' => $salesInvoice->id,
                    'invoice_no' => $invoiceNo,
                    'items_count' => $softwareDetails->count(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to create hr_sales_invoice from quotations", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Generate invoice number in format: TTC + YY + MM + 6-digit running number
     */
    public static function generateInvoiceNo(): string
    {
        $prefix = 'TTC' . now()->format('y') . now()->format('m');

        $lastInvoice = static::where('invoice_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(invoice_no, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_no, strlen($prefix));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
