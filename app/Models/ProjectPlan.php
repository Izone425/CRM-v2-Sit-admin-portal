<?php
namespace App\Models;

use App\Observers\ProjectPlanObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

#[ObservedBy([ProjectPlanObserver::class])]
class ProjectPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'sw_id',
        'project_task_id',
        'plan_start_date',
        'plan_end_date',
        'plan_duration',
        'actual_start_date',
        'actual_end_date',
        'actual_duration',
        'status',
        'notes',
    ];

    protected $casts = [
        'plan_duration' => 'integer',
        'actual_duration' => 'integer',
        'plan_start_date' => 'date',
        'plan_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function softwareHandover(): BelongsTo
    {
        return $this->belongsTo(SoftwareHandover::class, 'sw_id');
    }

    public function projectTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }

    // Calculate plan duration when dates are set
    public function calculatePlanDuration(): void
    {
        if ($this->plan_start_date && $this->plan_end_date) {
            $this->plan_duration = $this->countWeekdaysInclusive($this->plan_start_date, $this->plan_end_date);
            $this->save();
        }
    }

    // Calculate actual duration when dates are set
    public function calculateActualDuration(): void
    {
        if ($this->actual_start_date && $this->actual_end_date) {
            $this->actual_duration = $this->countWeekdaysInclusive($this->actual_start_date, $this->actual_end_date);
            $this->save();
        }
    }

    private function countWeekdaysInclusive(Carbon $startDate, Carbon $endDate): int
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $totalDays = $start->diffInDays($end) + 1;
        $fullWeeks = intdiv($totalDays, 7);
        $remainingDays = $totalDays % 7;

        $weekdays = $fullWeeks * 5;
        $startIsoDay = $start->dayOfWeekIso;

        for ($i = 0; $i < $remainingDays; $i++) {
            $dayIso = (($startIsoDay + $i - 1) % 7) + 1;
            if ($dayIso <= 5) {
                $weekdays++;
            }
        }

        return $weekdays;
    }

    // Auto-update status based on actual dates
    public function updateStatusBasedOnDates(): void
    {
        if ($this->actual_end_date) {
            $this->status = 'completed';
        } elseif ($this->actual_start_date) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }

    // Get percentage from the related ProjectTask
    public function getPercentageAttribute(): int
    {
        return $this->projectTask ? $this->projectTask->task_percentage : 0;
    }

    // Get module percentage
    public function getModulePercentageAttribute(): int
    {
        return $this->projectTask ? $this->projectTask->module_percentage : 0;
    }
}
