<?php

namespace App\Observers;

use App\Models\ProjectPlan;
use App\Models\SoftwareHandover;

class ProjectPlanObserver
{
    /**
     * Phase 2 (Online Webinar Training) Day 1 task — the start date of this task
     * is what gets mirrored onto software_handovers.webinar_training.
     */
    private const WEBINAR_DAY_1_TASK_ID = 48;

    public function saved(ProjectPlan $plan): void
    {
        $this->syncWebinarTraining($plan);
    }

    public function deleted(ProjectPlan $plan): void
    {
        $this->syncWebinarTraining($plan);
    }

    private function syncWebinarTraining(ProjectPlan $plan): void
    {
        if ((int) $plan->project_task_id !== self::WEBINAR_DAY_1_TASK_ID) {
            return;
        }

        if (!$plan->sw_id) {
            return;
        }

        $handover = SoftwareHandover::find($plan->sw_id);
        if (!$handover) {
            return;
        }

        $newDate = optional($plan->actual_start_date)->format('Y-m-d');

        if ((string) $handover->webinar_training !== (string) $newDate) {
            $handover->webinar_training = $newDate;
            $handover->saveQuietly();
        }
    }
}
