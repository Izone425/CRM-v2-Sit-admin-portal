<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QcTaskLabelOption extends Model
{
    use HasFactory;

    public const TIER_1 = 'tier1';
    public const TIER_2 = 'tier2';
    public const TIER_3 = 'tier3';

    public const TIERS = [self::TIER_1, self::TIER_2, self::TIER_3];

    protected $fillable = [
        'hr_version',
        'module',
        'tier',
        'value',
        'created_by',
    ];

    public static function forTier(string $tier, string $hrVersion, string $module): array
    {
        return self::where('tier', $tier)
            ->where('hr_version', $hrVersion)
            ->where('module', $module)
            ->orderBy('value')
            ->pluck('value')
            ->toArray();
    }

    public static function groupedByTier(string $hrVersion, string $module): array
    {
        $out = [];
        foreach (self::TIERS as $tier) {
            $out[$tier] = self::forTier($tier, $hrVersion, $module);
        }
        return $out;
    }
}
