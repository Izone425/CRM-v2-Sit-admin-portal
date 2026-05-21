<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'tasks';

    protected $fillable = [
        'task_id', 'title', 'related_ticket_id', 'parent_task_id',
        'rnd_on_hold', 'product_id', 'release_id', 'solution_id',
        'module_id', 'sub_module_id', 'software_selection', 'platform',
        'priority_id', 'status', 'assignee_ids', 'requestor_id',
        'task_size', 'description', 'remarks', 'submission_id',
        'creative_request_id', 'suggestion_id', 'srs_links',
        'start_date', 'delay_start_reason', 'eta_release', 'live_release',
        'due_date', 'submission_date', 'completion_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'eta_release' => 'date',
        'live_release' => 'date',
        'submission_date' => 'datetime',
        'completion_date' => 'datetime',
        'assignee_ids' => 'array',
        'platform' => 'array',
        'srs_links' => 'array',
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

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TaskPriority::class, 'priority_id');
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class, 'release_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Ticket::class, 'related_ticket_id');
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
