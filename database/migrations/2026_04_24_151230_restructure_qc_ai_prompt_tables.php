<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('qc_ai_prompt_tasks');

        Schema::create('qc_ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->enum('hr_version', ['v1', 'v2']);
            $table->string('module');
            $table->string('title');
            $table->string('label_tier1')->nullable();
            $table->string('label_tier2')->nullable();
            $table->string('label_tier3')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['hr_version', 'module']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('qc_ai_task_prompts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->longText('prompt');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('task_id')->references('id')->on('qc_ai_tasks')->cascadeOnDelete();
            $table->index(['task_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_ai_task_prompts');
        Schema::dropIfExists('qc_ai_tasks');
    }
};
