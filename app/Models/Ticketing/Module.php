<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'modules';
}
