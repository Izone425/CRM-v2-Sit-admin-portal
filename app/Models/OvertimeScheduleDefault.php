<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OvertimeScheduleDefault extends Model
{
    protected $fillable = [
        'day_of_week',
        'type',
        'user_id',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
