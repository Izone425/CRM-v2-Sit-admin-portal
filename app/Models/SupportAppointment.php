<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportAppointment extends Model
{
    protected $fillable = [
        'support_group_id',
        'user_id',
        'date',
        'type',
        'status',
        'causer_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(SupportGroup::class, 'support_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
