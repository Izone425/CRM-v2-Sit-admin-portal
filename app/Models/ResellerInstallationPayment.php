<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerInstallationPayment extends Model
{
    use HasFactory;

    protected $table = 'reseller_installation_payments';

    protected $fillable = [
        'reseller_id',
        'reseller_name',
        'attention_to',
        'customer_name',
        'installation_date',
        'installation_address',
        'quotation_path',
        'invoice_path',
        'status',
        'completed_at',
        'admin_remark',
        'finance_handover_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'installation_date' => 'date',
    ];

    /**
     * Get formatted ID attribute: RC{YY}{MM}-{XXXX}
     * Resets sequence every month
     */
    public function getFormattedIdAttribute()
    {
        if (!$this->id || !$this->created_at) {
            return null;
        }

        $year = $this->created_at->format('y');
        $month = $this->created_at->format('m');

        $monthStart = $this->created_at->copy()->startOfMonth();
        $monthEnd = $this->created_at->copy()->endOfMonth();

        $sequentialNumber = self::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('id', '<=', $this->id)
            ->count();

        return 'RC' . $year . $month . '-' . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
    }

    public function setCustomerNameAttribute($value)
    {
        $this->attributes['customer_name'] = strtoupper($value);
    }

    public function setInstallationAddressAttribute($value)
    {
        $this->attributes['installation_address'] = strtoupper($value);
    }

    public function getSalespersonNameAttribute()
    {
        if (!$this->attention_to) {
            return 'N/A';
        }

        $user = User::find($this->attention_to);
        return $user ? $user->name : 'N/A';
    }

    public function getResellerCompanyNameAttribute()
    {
        if (!$this->reseller_id) {
            return 'N/A';
        }

        $resellerLink = \Illuminate\Support\Facades\DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->where('reseller_id', $this->reseller_id)
            ->first();

        return $resellerLink ? $resellerLink->reseller_name : 'N/A';
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'attention_to');
    }
}
