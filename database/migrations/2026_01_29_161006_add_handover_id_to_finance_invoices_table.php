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
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('handover_id')->nullable()->after('reseller_handover_id');
            $table->index('handover_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropIndex(['handover_id']);
            $table->dropColumn('handover_id');
        });
    }
};
