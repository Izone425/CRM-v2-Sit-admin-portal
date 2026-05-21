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
        Schema::table('training_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('hrdf_claim_id')->nullable()->after('hrdf_application_status');
            $table->foreign('hrdf_claim_id')->references('id')->on('hrdf_claims')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_bookings', function (Blueprint $table) {
            $table->dropForeign(['hrdf_claim_id']);
            $table->dropColumn('hrdf_claim_id');
        });
    }
};
