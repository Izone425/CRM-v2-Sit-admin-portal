<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerHandoverFg extends Model
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
        'purchase_order',
        'autocount_invoice',
        'reseller_invoice',
        'autocount_invoice_number',
        'aci_submitted_at',
        'reseller_option',
        'official_receipt_number',
        'reseller_payment_slip',
        'rni_submitted_at',
        'status',
        'rejection_reason',
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
     * Get the formatted FG ID
     * Format: FG{YY}{MM}-{XXXX} e.g. FG2603-0001
     * Running number resets each month
     */
    public function getFgIdAttribute()
    {
        $createdAt = $this->created_at ?? now();
        $year = $createdAt->format('y');
        $month = $createdAt->format('m');

        $sequence = self::whereYear('created_at', $createdAt->year)
            ->whereMonth('created_at', $createdAt->month)
            ->where('id', '<=', $this->id)
            ->count();

        return 'FG' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
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

    public function getCategorizedFilesForModal(): array
    {
        $categorized = [
            'purchase_order' => [],
            'pending_timetec_invoice' => [],
            'pending_invoice_confirmation' => [],
        ];

        // Helper function to decode JSON or return single value
        $decodeFiles = function($field) {
            if (!$field) return [];
            return is_string($field) && json_decode($field)
                ? json_decode($field, true)
                : [$field];
        };

        // Purchase Order from Reseller
        foreach ($decodeFiles($this->purchase_order) as $index => $file) {
            $count = count($decodeFiles($this->purchase_order));
            $categorized['purchase_order'][] = [
                'name' => 'Reseller Purchase Order' . ($count > 1 ? ' #' . ($index + 1) : ''),
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
            $categorized['pending_timetec_invoice'][] = [
                'name' => 'Self Billed Invoice [Draft]' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Pending Invoice Confirmation Stage - Reseller Payment Slip
        foreach ($decodeFiles($this->reseller_payment_slip) as $index => $file) {
            $count = count($decodeFiles($this->reseller_payment_slip));
            $categorized['pending_invoice_confirmation'][] = [
                'name' => 'Reseller Payment Slip' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        return $categorized;
    }
}
