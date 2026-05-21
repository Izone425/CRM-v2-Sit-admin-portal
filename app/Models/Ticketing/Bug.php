<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bug extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'bugs';

    protected $fillable = [
        'bug_id', 'related_task_id', 'title', 'description',
        'product_id', 'release_id', 'solution_id', 'module_id', 'sub_module_id',
        'category_id', 'severity', 'platform', 'status', 'reporter_id',
        'submission_date', 'completion_date', 'assignee_ids',
    ];

    protected $casts = [
        'submission_date' => 'datetime',
        'completion_date' => 'datetime',
        'assignee_ids' => 'array',
        'platform' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function subModule(): BelongsTo
    {
        return $this->belongsTo(SubModule::class, 'sub_module_id');
    }

    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class, 'solution_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BugCategory::class, 'category_id');
    }

    public function relatedTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'related_task_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(TicketingUser::class, 'reporter_id');
    }

    public function getAssigneeNamesAttribute(): string
    {
        $ids = $this->assignee_ids ?? [];
        if (empty($ids)) {
            return '';
        }
        return TicketingUser::on($this->connection)->whereIn('id', $ids)->pluck('name')->implode(', ');
    }
}
