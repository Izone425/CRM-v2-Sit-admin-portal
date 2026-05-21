<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QcTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_version',
        'module',
        'title',
        'label_tier1',
        'label_tier2',
        'label_tier3',
        'created_by',
    ];

    public const HR_VERSIONS = ['v1', 'v2'];

    public const MODULES_V1 = ['Attendance', 'Leave', 'Claim', 'Payroll'];
    public const MODULES_V2 = ['Profile', 'Attendance', 'Leave', 'Claim', 'Payroll'];

    public static function modulesFor(string $version): array
    {
        return $version === 'v2' ? self::MODULES_V2 : self::MODULES_V1;
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(QcTaskPrompt::class, 'task_id')->orderBy('order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
