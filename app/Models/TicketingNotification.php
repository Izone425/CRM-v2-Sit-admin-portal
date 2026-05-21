<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TicketingNotification extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'notifications';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($notification) {
            if (empty($notification->id)) {
                $notification->id = Str::uuid()->toString();
            }
        });
    }

    /**
     * The ticketing system user this notification belongs to
     */
    public function notifiable(): BelongsTo
    {
        return $this->belongsTo(TicketingUser::class, 'notifiable_id');
    }

    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }
}
