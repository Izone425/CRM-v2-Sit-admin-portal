<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TerminationAnalysisNote extends Model
{
    protected $fillable = [
        'company_id',
        'is_excluded',
        'exclude_reason',
        'termination_reason',
        'updated_by',
    ];

    protected $casts = [
        'is_excluded' => 'boolean',
    ];

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
