<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Ticket extends Model
{
    use HasFactory;

    protected $connection = 'ticketingsystem_live';
    protected $table = 'tickets';

    public static function hasPendingPartyColumn(): bool
    {
        static $exists = null;
        if ($exists === null) {
            $exists = Schema::connection('ticketingsystem_live')->hasColumn('tickets', 'pending_party');
        }
        return $exists;
    }

    protected $fillable = [
        'ticket_id',
        'parent_ticket_id',
        'title',
        'status',
        'closure_reason',
        'rejection_reason',
        'submission_id',
        'srs_links',
        'completion_date',
        'kiv_reason',
        'pending_party',
        'isPassed',
        'passed_at',
        'product_id',
        'module_id',
        'priority_id',
        'company_name',
        'description',
        'zoho_id',
        'requestor_id',
        'assignee_ids',
        'created_date',
        'eta_release',
        'live_release',
        'device_type',
        'mobile_type',
        'browser_type',
        'device_id',
        'os_version',
        'app_version',
        'windows_version',
        'version_screenshot',
        'is_internal',
        'reopen_reason',
        'estimated_pdt_mandays',
        'estimated_rnd_mandays',
    ];

    protected $casts = [
        'created_date' => 'date',
        'eta_release' => 'date',
        'live_release' => 'date',
        'passed_at' => 'datetime',
        'completion_date' => 'date',
        'isPassed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'srs_links' => 'array',
        'assignee_ids' => 'array',
    ];

    public $incrementing = true;

    // ✅ Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(TicketProduct::class, 'product_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TicketModule::class, 'module_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class)->orderBy('created_at', 'desc');
    }

    // ✅ Add logs relationship
    public function logs()
    {
        return $this->hasMany(TicketLog::class, 'ticket_id')->orderBy('created_at', 'desc');
    }

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

    /**
     * Tasks related to this ticket (ticketingsystem_live.tasks)
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TicketTask::class, 'related_ticket_id');
    }

    /**
     * Ticketing system requestor (for observer notifications)
     * Separate from requestor() which maps to CRM User for backward compatibility
     */
    public function ticketingRequestor(): BelongsTo
    {
        return $this->belongsTo(TicketingUser::class, 'requestor_id');
    }

    /**
     * Get a fresh timestamp for the model.
     * Automatically adjusts timestamps to UTC-8 (Malaysia time)
     */
    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }

    // public static function generateTicketId(int $productId): string
    // {
    //     $productCode = TicketProduct::where('id', $productId)->value('product_code') ?? 'GEN';
    //     $yymm = now()->subHours(8)->format('ym');
    //     $prefix = "{$productCode}-{$yymm}-";

    //     $lastTicket = self::where('ticket_id', 'like', $prefix . '%')
    //         ->orderBy('id', 'desc')
    //         ->first();

    //     $next = 1;
    //     if ($lastTicket && preg_match('/-(\d+)$/', $lastTicket->ticket_id, $m)) {
    //         $next = (int) $m[1] + 1;
    //     }

    //     return sprintf('%s%04d', $prefix, $next);
    // }
}
