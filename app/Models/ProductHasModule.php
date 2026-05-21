<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductHasModule extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'product_has_modules';

    protected $fillable = [
        'product_id',
        'module_id',
    ];

    public function product()
    {
        return $this->belongsTo(TicketProduct::class, 'product_id');
    }

    public function module()
    {
        return $this->belongsTo(TicketModule::class, 'module_id');
    }

    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }
}
