<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerHandoverFf extends Model
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
        'vms_qty',
        'patrol_qty',
        'access_qty',
        'fcc_qty',
        'reseller_remark',
        'admin_reseller_remark',
        'timetec_proforma_invoice',
        'ttpi_submitted_at',
        'status',
        'confirmed_proceed_at',
        'completed_at',
        'payment_clicked_at',
    ];

    protected $casts = [
        'attendance_qty' => 'integer',
        'leave_qty' => 'integer',
        'claim_qty' => 'integer',
        'payroll_qty' => 'integer',
        'qf_master_qty' => 'integer',
        'vms_qty' => 'integer',
        'patrol_qty' => 'integer',
        'access_qty' => 'integer',
        'fcc_qty' => 'integer',
        'confirmed_proceed_at' => 'datetime',
        'completed_at' => 'datetime',
        'ttpi_submitted_at' => 'datetime',
        'payment_clicked_at' => 'datetime',
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
     * Get the formatted FF ID
     * Format: FF{YY}{MM}-{XXXX} e.g. FF2603-0001
     * Running number resets each month
     */
    public function getFfIdAttribute()
    {
        $createdAt = $this->created_at ?? now();
        $year = $createdAt->format('y');
        $month = $createdAt->format('m');

        $sequence = self::whereYear('created_at', $createdAt->year)
            ->whereMonth('created_at', $createdAt->month)
            ->where('id', '<=', $this->id)
            ->count();

        return 'FF' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
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
}
