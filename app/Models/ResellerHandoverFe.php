<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerHandoverFe extends Model
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
        'autocount_invoice',
        'reseller_invoice',
        'autocount_invoice_number',
        'ap_document',
        'aci_submitted_at',
        'reseller_option',
        'official_receipt_number',
        'cash_term_without_payment',
        'reseller_payment_slip',
        'rni_submitted_at',
        'reseller_payment_completed',
        'self_billed_einvoice',
        'self_billed_einvoice_submitted_at',
        'finance_payment_slip',
        'finance_payment_slip_submitted_at',
        'finance_payment_at',
        'offset_payment_at',
        'status',
        'confirmed_proceed_at',
        'completed_at',
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
        'finance_payment_slip_submitted_at' => 'datetime',
        'finance_payment_at' => 'datetime',
        'offset_payment_at' => 'datetime',
    ];

    public function setResellerCompanyNameAttribute($value)
    {
        $this->attributes['reseller_company_name'] = strtoupper($value);
    }

    public function setSubscriberNameAttribute($value)
    {
        $this->attributes['subscriber_name'] = strtoupper($value);
    }

    public function setResellerRemarkAttribute($value)
    {
        $this->attributes['reseller_remark'] = strtoupper($value);
    }

    /**
     * Get the formatted FE ID
     * Format: FE{YY}{MM}-{XXXX} e.g. FE2603-0001
     * Running number resets each month
     */
    public function getFeIdAttribute()
    {
        $createdAt = $this->created_at ?? now();
        $year = $createdAt->format('y');
        $month = $createdAt->format('m');

        $sequence = self::whereYear('created_at', $createdAt->year)
            ->whereMonth('created_at', $createdAt->month)
            ->where('id', '<=', $this->id)
            ->count();

        return 'FE' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the encrypted invoice URL
     */
    public function getInvoiceUrlAttribute()
    {
        if (!$this->timetec_proforma_invoice || !$this->subscriber_id) {
            return null;
        }

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
     * Get the encrypted AP Document URL
     */
    public function getApDocumentUrlAttribute()
    {
        if (!$this->ap_document || !$this->subscriber_id) {
            return null;
        }

        $license = DB::connection('frontenddb')
            ->table('crm_invoice_details')
            ->where('f_invoice_no', $this->ap_document)
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
            Log::error('AP Document ID encryption failed: ' . $e->getMessage(), [
                'license_id' => $license->f_id,
                'ap_document' => $this->ap_document
            ]);
            return null;
        }
    }

    /**
     * Get categorized files for the modal display
     */
    public function getCategorizedFilesForModal(): array
    {
        $categorized = [
            'pending_timetec_invoice' => [],
            'pending_invoice_confirmation' => [],
            'pending_timetec_finance' => [],
            'finance_payment' => [],
            'finance_self_billed_einvoice' => [],
        ];

        $decodeFiles = function($field) {
            if (!$field) return [];
            return is_string($field) && json_decode($field)
                ? json_decode($field, true)
                : [$field];
        };

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
            $categorized['pending_timetec_invoice'][] = [
                'name' => 'Self Billed Invoice [Draft]' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

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

        foreach ($decodeFiles($this->self_billed_einvoice) as $index => $file) {
            $count = count($decodeFiles($this->self_billed_einvoice));
            $categorized['finance_self_billed_einvoice'][] = [
                'name' => 'Self Billed E-Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        foreach ($decodeFiles($this->finance_payment_slip) as $index => $file) {
            $count = count($decodeFiles($this->finance_payment_slip));
            $categorized['finance_payment'][] = [
                'name' => 'Finance Payment Slip' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        return $categorized;
    }
}
