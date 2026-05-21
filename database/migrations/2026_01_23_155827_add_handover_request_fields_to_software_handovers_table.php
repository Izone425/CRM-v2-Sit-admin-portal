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
        Schema::table('software_handovers', function (Blueprint $table) {
            $table->timestamp('handover_requested_at')->nullable()->after('project_plan_generated_at');
            $table->unsignedBigInteger('handover_requested_by')->nullable()->after('handover_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_handovers', function (Blueprint $table) {
            $table->dropColumn(['handover_requested_at', 'handover_requested_by']);
        });
    }
};
