<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class Solution extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'solutions';
}
