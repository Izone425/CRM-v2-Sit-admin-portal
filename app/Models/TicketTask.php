<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketTask extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'tasks';

    protected $casts = [
        'assignee_ids' => 'array',
        'srs_links' => 'array',
        'start_date' => 'date',
        'due_date' => 'date',
        'eta_release' => 'date',
        'live_release' => 'date',
        'completion_date' => 'date',
    ];

    /**
     * Get assigned ticketing system users from assignee_ids JSON
     */
    public function getAssignees()
    {
        $ids = $this->assignee_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return TicketingUser::whereIn('id', $ids)->get();
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'related_ticket_id');
    }

    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }
}
