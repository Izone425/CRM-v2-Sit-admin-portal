<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcTaskPromptAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'prompt_id',
        'file_path',
        'original_name',
        'order',
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(QcTaskPrompt::class, 'prompt_id');
    }
}
