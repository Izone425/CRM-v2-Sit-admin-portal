<?php

namespace App\Models\Ticketing;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'products';
}
