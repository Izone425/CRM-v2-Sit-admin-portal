<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivateSessionLog extends Model
{
    protected $fillable = [
        'lead_id',
        'software_handover_id',
        'user_id',
        'user_name',
        'appointments_updated_count',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function softwareHandover(): BelongsTo
    {
        return $this->belongsTo(SoftwareHandover::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
