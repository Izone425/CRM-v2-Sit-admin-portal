<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BugComment extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'bug_comments';

    protected $fillable = [
        'bug_id', 'user_id', 'comment', 'is_edited', 'edited_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'is_edited' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TicketingUser::class, 'user_id');
    }
}
