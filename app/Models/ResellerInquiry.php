<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerInquiry extends Model
{
    use HasFactory;

    protected $table = 'reseller_inquiries';

    protected $fillable = [
        'reseller_id',
        'reseller_name',
        'subscriber_type',
        'subscriber_id',
        'subscriber_name',
        'title',
        'description',
        'attachment_path',
        'status',
        'admin_remark',
        'admin_attachment_path',
        'completed_at',
        'rejected_at',
        'reject_reason',
        'reject_attachment_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'reject_attachment_path' => 'array',
    ];

    /**
     * Get formatted ID attribute: RA{YY}{MM}-{XXXX}
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

        return 'RA' . $year . $month . '-' . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Set the subscriber name attribute to uppercase
     */
    public function setSubscriberNameAttribute($value)
    {
        $this->attributes['subscriber_name'] = strtoupper($value);
    }

    /**
     * Get the reseller that owns the inquiry
     */
    public function reseller()
    {
        return $this->belongsTo(Reseller::class, 'reseller_id', 'reseller_id');
    }

    /**
     * Get the reseller company name from frontenddb
     */
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
