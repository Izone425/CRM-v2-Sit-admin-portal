<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SalespersonTodoList extends Model
{
    use HasFactory;

    protected $table = 'salesperson_todo_list';

    protected $fillable = [
        'todo_id',
        'salesperson_id',
        'lead_id',
        'company_name',
        'reminder_date',
        'remark',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'reminder_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function getDaysLeftAttribute(): int
    {
        if ($this->status === 'completed') {
            return 0;
        }

        $today = Carbon::today();
        $reminderDate = Carbon::parse($this->reminder_date);

        return $today->diffInDays($reminderDate, false);
    }

    public function getDaysLeftColorAttribute(): string
    {
        $daysLeft = $this->days_left;

        if ($daysLeft < 0) {
            return 'danger'; // Overdue - red
        } elseif ($daysLeft === 0) {
            return 'warning'; // Today - yellow
        } elseif ($daysLeft <= 3) {
            return 'warning'; // Within 3 days - yellow
        } else {
            return 'success'; // More than 3 days - green
        }
    }

    public static function generateTodoId(): string
    {
        $year = date('y'); // 26 for 2026

        // Get the latest todo ID for this year
        $latestTodo = self::where('todo_id', 'LIKE', "TL_{$year}%")
            ->orderByRaw('CAST(SUBSTRING(todo_id, 6) AS UNSIGNED) DESC')
            ->first();

        $nextSequence = 1;
        if ($latestTodo) {
            preg_match("/TL_{$year}(\d+)/", $latestTodo->todo_id, $matches);
            $nextSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1;
        }

        $sequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "TL_{$year}{$sequence}";
    }
}
