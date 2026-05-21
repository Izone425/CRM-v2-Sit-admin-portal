<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->text('cash_term_without_payment')->nullable()->after('official_receipt_number');
            $table->text('reseller_payment_slip')->nullable()->after('cash_term_without_payment');
            $table->timestamp('rni_submitted_at')->nullable()->after('reseller_payment_slip');
            $table->boolean('reseller_payment_completed')->default(false)->after('rni_submitted_at');
            $table->text('self_billed_einvoice')->nullable()->after('reseller_payment_completed');
            $table->timestamp('self_billed_einvoice_submitted_at')->nullable()->after('self_billed_einvoice');
            $table->timestamp('offset_payment_at')->nullable()->after('self_billed_einvoice_submitted_at');
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->text('cash_term_without_payment')->nullable()->after('official_receipt_number');
            $table->text('reseller_payment_slip')->nullable()->after('cash_term_without_payment');
            $table->timestamp('rni_submitted_at')->nullable()->after('reseller_payment_slip');
            $table->boolean('reseller_payment_completed')->default(false)->after('rni_submitted_at');
            $table->text('self_billed_einvoice')->nullable()->after('reseller_payment_completed');
            $table->timestamp('self_billed_einvoice_submitted_at')->nullable()->after('self_billed_einvoice');
            $table->timestamp('offset_payment_at')->nullable()->after('self_billed_einvoice_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->dropColumn([
                'cash_term_without_payment',
                'reseller_payment_slip',
                'rni_submitted_at',
                'reseller_payment_completed',
                'self_billed_einvoice',
                'self_billed_einvoice_submitted_at',
                'offset_payment_at',
            ]);
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->dropColumn([
                'cash_term_without_payment',
                'reseller_payment_slip',
                'rni_submitted_at',
                'reseller_payment_completed',
                'self_billed_einvoice',
                'self_billed_einvoice_submitted_at',
                'offset_payment_at',
            ]);
        });
    }
};
