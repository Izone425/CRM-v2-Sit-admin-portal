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
        Schema::table('reseller_handover_ffs', function (Blueprint $table) {
            $table->timestamp('payment_clicked_at')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_handover_ffs', function (Blueprint $table) {
            $table->dropColumn('payment_clicked_at');
        });
    }
};
