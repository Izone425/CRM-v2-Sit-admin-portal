<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskComment extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'task_comments';

    protected $fillable = [
        'task_id', 'user_id', 'comment', 'is_edited', 'edited_at', 'attachments',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'attachments' => 'array',
        'is_edited' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TicketingUser::class, 'user_id');
    }
}
