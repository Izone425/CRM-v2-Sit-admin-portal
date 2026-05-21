<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            if (!Schema::hasColumn('reseller_handover_fes', 'finance_payment_at')) {
                $table->timestamp('finance_payment_at')->nullable()->after('self_billed_einvoice_submitted_at');
            }
        });

        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            if (!Schema::hasColumn('reseller_handover_fds', 'finance_payment_at')) {
                $table->timestamp('finance_payment_at')->nullable()->after('self_billed_einvoice_submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->dropColumn(['finance_payment_at']);
        });

        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->dropColumn(['finance_payment_at']);
        });
    }
};
