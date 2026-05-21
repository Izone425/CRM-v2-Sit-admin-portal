<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImplementerHandoverRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sw_id',
        'implementer_name',
        'company_name',
        'date_request',
        'status',
        'team_lead_remark',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
    ];

    protected $casts = [
        'date_request' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the software handover that owns this request
     */
    public function softwareHandover()
    {
        return $this->belongsTo(SoftwareHandover::class, 'sw_id');
    }
}
