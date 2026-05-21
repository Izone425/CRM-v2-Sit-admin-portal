<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcTaskPrompt extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'task_id',
        'prompt',
        'order',
        'status',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(QcTask::class, 'task_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(QcTaskPromptAttachment::class, 'prompt_id')->orderBy('order');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
