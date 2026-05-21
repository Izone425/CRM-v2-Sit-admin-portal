<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->boolean('task_completed')->default(false)->after('admin_remark');
            $table->timestamp('task_completed_at')->nullable()->after('task_completed');
            $table->unsignedBigInteger('task_completed_by')->nullable()->after('task_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->dropColumn(['task_completed', 'task_completed_at', 'task_completed_by']);
        });
    }
};
