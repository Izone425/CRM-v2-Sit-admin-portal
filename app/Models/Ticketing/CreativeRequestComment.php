<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreativeRequestComment extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'creative_request_comments';

    protected $fillable = [
        'request_id', 'user_id', 'comment', 'is_edited', 'edited_at', 'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TicketingUser::class, 'user_id');
    }

    public function creativeRequest(): BelongsTo
    {
        return $this->belongsTo(CreativeRequest::class, 'request_id');
    }
}
