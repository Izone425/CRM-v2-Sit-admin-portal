<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class TaskLog extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'task_logs';
}
