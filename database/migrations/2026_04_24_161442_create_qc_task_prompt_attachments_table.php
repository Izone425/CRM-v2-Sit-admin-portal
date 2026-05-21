<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_task_prompt_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prompt_id');
            $table->string('file_path');
            $table->string('original_name');
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('prompt_id')->references('id')->on('qc_task_prompts')->cascadeOnDelete();
            $table->index(['prompt_id', 'order']);
        });

        // Migrate any existing single-attachment data into the new table
        $existing = DB::table('qc_task_prompts')
            ->whereNotNull('attachment_path')
            ->get(['id', 'attachment_path', 'attachment_original_name']);

        foreach ($existing as $row) {
            DB::table('qc_task_prompt_attachments')->insert([
                'prompt_id'     => $row->id,
                'file_path'     => $row->attachment_path,
                'original_name' => $row->attachment_original_name ?: basename($row->attachment_path),
                'order'         => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        Schema::table('qc_task_prompts', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_original_name']);
        });
    }

    public function down(): void
    {
        Schema::table('qc_task_prompts', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('status');
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
        });

        Schema::dropIfExists('qc_task_prompt_attachments');
    }
};
