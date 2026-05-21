<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OffsetPaymentHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'requestor_id',
        'company_name',
        'invoice_no',
        'payment_slip',
        'status',
        'reject_reason',
        'completed_at',
    ];

    protected $casts = [
        'payment_slip' => 'array',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['formatted_id'];

    /**
     * Set the company name to uppercase
     */
    public function setCompanyNameAttribute($value)
    {
        $this->attributes['company_name'] = strtoupper($value);
    }

    /**
     * Get the formatted FI ID
     * Format: FI{YY}{MM}-{XXXX} e.g. FI2603-0001
     * Running number resets each month
     */
    public function getFormattedIdAttribute()
    {
        $createdAt = $this->created_at ?? now();
        $year = $createdAt->format('y');
        $month = $createdAt->format('m');

        $sequence = self::whereYear('created_at', $createdAt->year)
            ->whereMonth('created_at', $createdAt->month)
            ->where('id', '<=', $this->id)
            ->count();

        return 'FI' . $year . $month . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the requestor (user who created this)
     */
    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }
}
