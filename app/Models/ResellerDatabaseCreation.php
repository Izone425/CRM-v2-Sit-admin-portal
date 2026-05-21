<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerDatabaseCreation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'reseller_name',
        'reseller_company_name',
        'company_name',
        'ssm_number',
        'tax_identification_number',
        'pic_name',
        'pic_phone',
        'master_login_email',
        'modules',
        'headcount',
        'reseller_remark',
        'status',
        'admin_remark',
        'admin_attachment_path',
        'reject_reason',
        'completed_at',
        'rejected_at',
    ];

    protected $casts = [
        'modules' => 'array',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get formatted ID attribute: RB{YY}{MM}-{XXXX}
     * Resets sequence every month
     */
    public function getFormattedIdAttribute()
    {
        if (!$this->id || !$this->created_at) {
            return null;
        }

        $year = $this->created_at->format('y');
        $month = $this->created_at->format('m');

        // Get the sequential number for this month
        $monthStart = $this->created_at->copy()->startOfMonth();
        $monthEnd = $this->created_at->copy()->endOfMonth();

        $sequentialNumber = self::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('id', '<=', $this->id)
            ->count();

        return 'RB' . $year . $month . '-' . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
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
}
