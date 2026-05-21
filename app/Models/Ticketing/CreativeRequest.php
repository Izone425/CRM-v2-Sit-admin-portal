<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreativeRequest extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'creative_requests';

    protected $fillable = [
        'request_id', 'related_ticket_id', 'related_task_id',
        'product_id', 'solution_id', 'module_id', 'sub_module_id',
        'title', 'description', 'priority', 'category', 'status',
        'assignee_id', 'requestor_id', 'expected_completion_date', 'due_date',
    ];

    protected $casts = [
        'expected_completion_date' => 'date',
        'due_date' => 'date',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function module(): BelongsTo { return $this->belongsTo(Module::class); }
    public function subModule(): BelongsTo { return $this->belongsTo(SubModule::class, 'sub_module_id'); }
    public function solution(): BelongsTo { return $this->belongsTo(Solution::class, 'solution_id'); }
    public function requestor(): BelongsTo { return $this->belongsTo(TicketingUser::class, 'requestor_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(TicketingUser::class, 'assignee_id'); }

    public function comments(): HasMany
    {
        return $this->hasMany(CreativeRequestComment::class, 'request_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CreativeRequestAttachment::class, 'request_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CreativeRequestLog::class, 'creative_request_id');
    }
}
