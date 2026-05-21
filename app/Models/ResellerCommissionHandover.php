<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerCommissionHandover extends Model
{
    /**
     * Get the formatted FH ID
     * Format: FH{YY}{MM}-{XXXX} e.g. FH2603-0001
     * Running number resets each month
     */
    public function getFhIdAttribute()
    {
        $createdAt = $this->created_at ?? now();
        $year = $createdAt->format('y');
        $month = $createdAt->format('m');

        $sequence = self::whereYear('created_at', $createdAt->year)
            ->whereMonth('created_at', $createdAt->month)
            ->where('status', '!=', 'credit_note')
            ->where('id', '<=', $this->id)
            ->count();

        return 'FH' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the encrypted AP Invoice (PI No) URL
     */
    public function getApInvoiceUrlAttribute()
    {
        if (!$this->ap_invoice_no) {
            return null;
        }

        $license = DB::connection('frontenddb')
            ->table('crm_invoice_details')
            ->where('f_invoice_no', $this->ap_invoice_no)
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
            Log::error('AP Invoice ID encryption failed: ' . $e->getMessage(), [
                'license_id' => $license->f_id,
                'invoice_no' => $this->ap_invoice_no
            ]);
            return null;
        }
    }

    /**
     * Get the encrypted TT Invoice (TTPI No) URL
     */
    public function getTtInvoiceUrlAttribute()
    {
        if (!$this->tt_invoice_no) {
            return null;
        }

        $license = DB::connection('frontenddb')
            ->table('crm_invoice_details')
            ->where('f_invoice_no', $this->tt_invoice_no)
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
            Log::error('TT Invoice ID encryption failed: ' . $e->getMessage(), [
                'license_id' => $license->f_id,
                'invoice_no' => $this->tt_invoice_no
            ]);
            return null;
        }
    }

    protected $fillable = [
        'reseller_id',
        'ap_invoice_no',
        'tt_invoice_no',
        'autocount_inv_no',
        'reseller_name',
        'subscriber_name',
        'amount',
        'currency',
        'status',
        'payment_slip',
        'self_billed_einvoice',
        'self_billed_einvoice_uploaded_at',
        'reseller_proceeded_at',
        'payment_slip_uploaded_at',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reseller_proceeded_at' => 'datetime',
        'payment_slip_uploaded_at' => 'datetime',
        'self_billed_einvoice_uploaded_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
