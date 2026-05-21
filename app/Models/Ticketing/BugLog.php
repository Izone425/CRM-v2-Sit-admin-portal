<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class BugLog extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'bug_logs';
}
