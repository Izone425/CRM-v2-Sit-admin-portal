<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'reseller_name',
        'reseller_company_name',
        'subscriber_id',
        'subscriber_name',
        'subscriber_status',
        'category',
        'attendance_qty',
        'leave_qty',
        'claim_qty',
        'payroll_qty',
        'qf_master_qty',
        'reseller_remark',
        'admin_reseller_remark',
        'timetec_proforma_invoice',
        'ttpi_submitted_at',
        'status',
        'confirmed_proceed_at',
        'autocount_invoice',
        'reseller_invoice',
        'autocount_invoice_number',
        'aci_submitted_at',
        'reseller_option',
        'completed_at',
        'cash_term_without_payment',
        'reseller_payment_slip',
        'rni_submitted_at',
        'official_receipt_number',
        'reseller_payment_completed',
        'self_billed_einvoice',
        'self_billed_einvoice_submitted_at',
        'offset_payment_at'
    ];

    protected $casts = [
        'attendance_qty' => 'integer',
        'leave_qty' => 'integer',
        'claim_qty' => 'integer',
        'payroll_qty' => 'integer',
        'qf_master_qty' => 'integer',
        'confirmed_proceed_at' => 'datetime',
        'completed_at' => 'datetime',
        'ttpi_submitted_at' => 'datetime',
        'aci_submitted_at' => 'datetime',
        'rni_submitted_at' => 'datetime',
        'self_billed_einvoice_submitted_at' => 'datetime',
        'offset_payment_at' => 'datetime',
    ];

    /**
     * Set the reseller company name to uppercase
     */
    public function setResellerCompanyNameAttribute($value)
    {
        $this->attributes['reseller_company_name'] = strtoupper($value);
    }

    /**
     * Set the subscriber name to uppercase
     */
    public function setSubscriberNameAttribute($value)
    {
        $this->attributes['subscriber_name'] = strtoupper($value);
    }

    /**
     * Set the reseller remark to uppercase
     */
    public function setResellerRemarkAttribute($value)
    {
        $this->attributes['reseller_remark'] = strtoupper($value);
    }

    /**
     * Get the formatted FB ID
     * Format: FB{YY}{MM}-{XXXX} e.g. FB2602-0001
     * Running number resets each month
     */
    public function getFbIdAttribute()
    {
        $createdAt = $this->created_at ?? now();
        $year = $createdAt->format('y');
        $month = $createdAt->format('m');

        // Count how many records were created before this one in the same month
        $sequence = self::whereYear('created_at', $createdAt->year)
            ->whereMonth('created_at', $createdAt->month)
            ->where('id', '<=', $this->id)
            ->count();

        return 'FB' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the related finance invoice
     */
    public function financeInvoice()
    {
        return $this->hasOne(FinanceInvoice::class, 'reseller_handover_id');
    }

    /**
     * Get the encrypted invoice URL
     */
    public function getInvoiceUrlAttribute()
    {
        if (!$this->timetec_proforma_invoice || !$this->subscriber_id) {
            return null;
        }

        // Get the f_id from crm_expiring_license table using invoice number and company id
        $license = DB::connection('frontenddb')
            ->table('crm_invoice_details')
            ->where('f_invoice_no', $this->timetec_proforma_invoice)
            ->first(['f_id']);

        if (!$license || !$license->f_id) {
            return null;
        }

        $aesKey = 'Epicamera@99';
        try {
            $encrypted = openssl_encrypt($license->f_id, "AES-128-ECB", $aesKey);
            $encryptedBase64 = base64_encode($encrypted);
            return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;
        } catch (\Exception $e) {
            Log::error('License ID encryption failed: ' . $e->getMessage(), [
                'license_id' => $license->f_id,
                'invoice_no' => $this->timetec_proforma_invoice
            ]);
            return null;
        }
    }

    /**
     * Get all files for this handover in a formatted array
     */
    public function getAllFilesForModal(): array
    {
        $files = [];

        // Helper function to decode JSON or return single value
        $decodeFiles = function($field) {
            if (!$field) return [];
            return is_string($field) && json_decode($field)
                ? json_decode($field, true)
                : [$field];
        };

        // PDF File
        foreach ($decodeFiles($this->pdf_file) as $index => $file) {
            $count = count($decodeFiles($this->pdf_file));
            $files[] = [
                'name' => 'PDF File' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Autocount Invoice
        foreach ($decodeFiles($this->autocount_invoice) as $index => $file) {
            $count = count($decodeFiles($this->autocount_invoice));
            $invoiceNumber = $this->autocount_invoice_number ? $this->autocount_invoice_number : 'AutoCount Invoice' . ($count > 1 ? ' #' . ($index + 1) : '');
            $files[] = [
                'name' => $invoiceNumber,
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Reseller Invoice
        foreach ($decodeFiles($this->reseller_invoice) as $index => $file) {
            $count = count($decodeFiles($this->reseller_invoice));
            $files[] = [
                'name' => 'Reseller Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Reseller Normal Invoice
        foreach ($decodeFiles($this->cash_term_without_payment) as $index => $file) {
            $count = count($decodeFiles($this->cash_term_without_payment));
            $files[] = [
                'name' => 'Reseller Normal Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Reseller Payment Slip
        foreach ($decodeFiles($this->reseller_payment_slip) as $index => $file) {
            $count = count($decodeFiles($this->reseller_payment_slip));
            $files[] = [
                'name' => 'Reseller Payment Slip' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        return $files;
    }

    /**
     * Get categorized files for modal display
     */
    public function getCategorizedFilesForModal(): array
    {
        $categorized = [
            'pending_quotation_confirmation' => [],
            'pending_timetec_invoice' => [],
            'pending_invoice_confirmation' => [],
            'pending_timetec_license' => [],
            'pending_timetec_finance' => [],
            'completed' => [],
        ];

        // Helper function to decode JSON or return single value
        $decodeFiles = function($field) {
            if (!$field) return [];
            return is_string($field) && json_decode($field)
                ? json_decode($field, true)
                : [$field];
        };

        // Pending Confirmation Stage - PDF File
        foreach ($decodeFiles($this->pdf_file) as $index => $file) {
            $count = count($decodeFiles($this->pdf_file));
            $categorized['pending_confirmation'][] = [
                'name' => 'PDF File' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Pending TimeTec Invoice Stage - Autocount Invoice & Reseller Invoice
        foreach ($decodeFiles($this->autocount_invoice) as $index => $file) {
            $count = count($decodeFiles($this->autocount_invoice));
            $invoiceNumber = $this->autocount_invoice_number ? $this->autocount_invoice_number : 'AutoCount Invoice' . ($count > 1 ? ' #' . ($index + 1) : '');
            $categorized['pending_timetec_invoice'][] = [
                'name' => $invoiceNumber,
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        foreach ($decodeFiles($this->reseller_invoice) as $index => $file) {
            $count = count($decodeFiles($this->reseller_invoice));
            $financeInvoice = $this->financeInvoice;
            $amountInfo = '';
            if ($financeInvoice) {
                $currency = $financeInvoice->currency ?? 'MYR';
                $amount = number_format($financeInvoice->reseller_commission_amount, 2);
                $amountInfo = " [{$currency} {$amount}]";
            }
            $categorized['pending_timetec_invoice'][] = [
                'name' => 'Self Billed Invoice [Draft]' . $amountInfo . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Pending Invoice Confirmation Stage - Reseller Normal Invoice & Payment Slip
        foreach ($decodeFiles($this->cash_term_without_payment) as $index => $file) {
            $count = count($decodeFiles($this->cash_term_without_payment));
            $categorized['pending_invoice_confirmation'][] = [
                'name' => 'Reseller Normal Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        foreach ($decodeFiles($this->reseller_payment_slip) as $index => $file) {
            $count = count($decodeFiles($this->reseller_payment_slip));
            $categorized['pending_invoice_confirmation'][] = [
                'name' => 'Reseller Payment Slip' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Pending TimeTec Finance Stage - Self Billed E-Invoice
        foreach ($decodeFiles($this->self_billed_einvoice) as $index => $file) {
            $count = count($decodeFiles($this->self_billed_einvoice));
            $categorized['pending_timetec_finance'][] = [
                'name' => 'Self Billed E-Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Pending TimeTec License Stage - No files, only official receipt number shown in modal
        // Files are not needed in this stage

        return $categorized;
    }
}
