<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class CreativeRequestLog extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'creative_request_logs';

    protected $fillable = [
        'creative_request_id', 'user_id', 'user_name',
        'action', 'action_description',
        'old_value', 'new_value', 'change_type', 'source',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];
}
