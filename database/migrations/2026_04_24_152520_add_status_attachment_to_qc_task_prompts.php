<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_task_prompts', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed'])->default('pending')->after('prompt');
            $table->string('attachment_path')->nullable()->after('status');
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
            $table->timestamp('completed_at')->nullable()->after('attachment_original_name');
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');

            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('qc_task_prompts', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropColumn(['status', 'attachment_path', 'attachment_original_name', 'completed_at', 'completed_by']);
        });
    }
};
