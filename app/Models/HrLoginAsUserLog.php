<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrLoginAsUserLog extends Model
{
    use HasFactory;

    protected $table = 'hr_login_as_user_logs';

    protected $fillable = [
        'causer_id',
        'causer_name',
        'target_email',
        'hr_user_id',
        'hr_company_id',
        'software_handover_id',
        'ip_address',
        'user_agent',
        'status',
        'error_message',
    ];

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function softwareHandover(): BelongsTo
    {
        return $this->belongsTo(SoftwareHandover::class, 'software_handover_id');
    }
}
