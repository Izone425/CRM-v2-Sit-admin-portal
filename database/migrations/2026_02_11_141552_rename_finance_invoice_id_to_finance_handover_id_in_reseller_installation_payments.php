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
        Schema::table('reseller_installation_payments', function (Blueprint $table) {
            $table->renameColumn('finance_invoice_id', 'finance_handover_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_installation_payments', function (Blueprint $table) {
            $table->renameColumn('finance_handover_id', 'finance_invoice_id');
        });
    }
};
