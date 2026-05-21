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
        Schema::table('implementer_handover_requests', function (Blueprint $table) {
            $table->string('status')->nullable()->after('date_request'); // pending, approved, rejected
            $table->text('team_lead_remark')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('team_lead_remark');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementer_handover_requests', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'team_lead_remark',
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
            ]);
        });
    }
};
