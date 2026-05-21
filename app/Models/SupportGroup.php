<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SupportGroup extends Model
{
    protected $fillable = ['name', 'sort_order', 'created_by'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'support_group_user')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
