<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class BugCategory extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'bug_categories';
}
