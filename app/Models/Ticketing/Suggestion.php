<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'suggestions';

    protected $fillable = [
        'suggestion_id', 'related_ticket_id', 'related_task_id',
        'product_id', 'solution_id', 'module_id', 'sub_module_id',
        'title', 'description', 'priority', 'category', 'status',
        'requestor_id', 'reference_link',
    ];

    protected $casts = [
        'related_ticket_id' => 'array',
        'related_task_id' => 'array',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function module(): BelongsTo { return $this->belongsTo(Module::class); }
    public function subModule(): BelongsTo { return $this->belongsTo(SubModule::class, 'sub_module_id'); }
    public function solution(): BelongsTo { return $this->belongsTo(Solution::class, 'solution_id'); }
    public function requestor(): BelongsTo { return $this->belongsTo(TicketingUser::class, 'requestor_id'); }
}
