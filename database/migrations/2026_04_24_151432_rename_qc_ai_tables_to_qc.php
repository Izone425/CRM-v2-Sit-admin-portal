<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('qc_ai_task_prompts') && !Schema::hasTable('qc_task_prompts')) {
            Schema::rename('qc_ai_task_prompts', 'qc_task_prompts');
        }

        if (Schema::hasTable('qc_ai_tasks') && !Schema::hasTable('qc_tasks')) {
            Schema::rename('qc_ai_tasks', 'qc_tasks');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('qc_tasks') && !Schema::hasTable('qc_ai_tasks')) {
            Schema::rename('qc_tasks', 'qc_ai_tasks');
        }

        if (Schema::hasTable('qc_task_prompts') && !Schema::hasTable('qc_ai_task_prompts')) {
            Schema::rename('qc_task_prompts', 'qc_ai_task_prompts');
        }
    }
};
