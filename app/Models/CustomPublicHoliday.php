<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CustomPublicHoliday extends Model
{
    protected $fillable = [
        'day_of_week',
        'date',
        'name',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->date) {
                $model->day_of_week = Carbon::parse($model->date)->dayOfWeekIso;
            }
        });
    }

    public function getDayNameAttribute(): string
    {
        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];
        return $days[$this->day_of_week] ?? '';
    }
}
