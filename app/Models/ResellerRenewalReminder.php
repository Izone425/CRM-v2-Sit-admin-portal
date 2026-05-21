<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerRenewalReminder extends Model
{
    use HasFactory;

    protected $table = 'reseller_renewal_reminders';

    protected $fillable = [
        'reseller_id',
        'f_company_id',
        'f_invoice_no',
        'f_company_name',
        'added_by',
    ];

    protected $casts = [
        'f_company_id' => 'integer',
    ];
}
