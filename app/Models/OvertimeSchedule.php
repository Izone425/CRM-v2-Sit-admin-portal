<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OvertimeSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'date',
        'type',
        'user_id',
        'status',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper function to get the Sunday date (for weekend rows where date = Saturday)
    public function getSundayDateAttribute()
    {
        return $this->date->copy()->addDay();
    }
}
