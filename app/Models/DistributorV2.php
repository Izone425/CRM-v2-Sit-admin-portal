<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DistributorV2 extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guard = 'distributor';

    protected $table = 'distributor_v2';

    protected $fillable = [
        'name',
        'email',
        'password',
        'plain_password',
        'company_name',
        'phone',
        'status',
        'email_verified_at',
        'last_login_at',
        'commission_rate',
        'territory',
        'contact_person',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'ssm_number',
        'tax_identification_number',
        'sst_category',
        'reseller_id',
        'parent_reseller_id',
        'debtor_code',
        'creditor_code',
        'payment_type',
        'email_notification',
        'trial_account_feature',
        'installation_payment_feature',
        'block_payment_gateway',
        'renewal_quotation',
        'bill_as_reseller',
        'bill_as_end_user',
        'usd_with_quotation',
        'usd_with_invoice',
        'bypass_invoice',
        'reseller_commission',
        'advanced_modules',
        'partner_application_id',
        'modules',
        'headcount',
        'hr_account_id',
        'hr_company_id',
        'hr_user_id',
        'crm_buffer_license_id',
    ];

    protected $hidden = [
        'password',
        'plain_password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'commission_rate' => 'decimal:2',
        'modules' => 'array',
        'headcount' => 'integer',
    ];

    public function partnerApplication()
    {
        return $this->belongsTo(PartnerApplication::class, 'partner_application_id');
    }

    public function reseller()
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }
}
