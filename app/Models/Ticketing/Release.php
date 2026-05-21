<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'releases';

    protected $fillable = [
        'product_id', 'solution_id', 'module_id', 'platform',
        'version', 'status', 'is_locked',
        'planned_live_date', 'actual_live_date',
    ];

    protected $casts = [
        'planned_live_date' => 'date',
        'actual_live_date' => 'date',
        'is_locked' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class, 'solution_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'release_id');
    }

    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class, 'release_id');
    }
}
