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
        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->string('finance_payment_slip')->nullable()->after('self_billed_einvoice_submitted_at');
            $table->timestamp('finance_payment_slip_submitted_at')->nullable()->after('finance_payment_slip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->dropColumn(['finance_payment_slip', 'finance_payment_slip_submitted_at']);
        });
    }
};
