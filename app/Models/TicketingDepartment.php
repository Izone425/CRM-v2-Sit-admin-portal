<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketingDepartment extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'departments';

    protected $fillable = [
        'name',
        'is_active',
    ];

    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }
}
