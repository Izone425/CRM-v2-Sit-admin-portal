<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class SubModule extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'sub_modules';
}
