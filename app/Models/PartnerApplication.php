<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_type',
        'categories',
        'headcount',
        'company_name',
        'address',
        'state',
        'postcode',
        'country',
        'telephone',
        'company_website',
        'business_type',
        'industry',
        'years_in_business',
        'email',
        'password',
        'plain_password',
        'mobile_phone',
        'first_name',
        'last_name',
        'designation',
        'existing_fingertec_reseller',
        'consent_setup_permission',
        'consent_marketing',
        'status',
        'reviewed_at',
        'reviewed_by',
        'review_remark',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
        'plain_password',
    ];

    protected $casts = [
        'categories' => 'array',
        'headcount' => 'integer',
        'existing_fingertec_reseller' => 'boolean',
        'consent_setup_permission' => 'boolean',
        'consent_marketing' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
