<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationSetting extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'user_notification_settings';

    protected $fillable = [
        'user_id',
        'notification_key',
        'enabled',
        'setting_value',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }
}
