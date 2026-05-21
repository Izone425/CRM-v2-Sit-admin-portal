<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'version',
        'module_type',
        'file_name',
        'file_path',
        'is_link',
        'training_type',
        'uploaded_by',
    ];

    protected $casts = [
        'is_link' => 'boolean',
        'training_type' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
