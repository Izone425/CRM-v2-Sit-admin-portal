<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->text('autocount_invoice')->nullable()->after('timetec_proforma_invoice');
            $table->text('reseller_invoice')->nullable()->after('autocount_invoice');
            $table->string('autocount_invoice_number')->nullable()->after('reseller_invoice');
            $table->timestamp('aci_submitted_at')->nullable()->after('autocount_invoice_number');
            $table->string('reseller_option')->nullable()->after('aci_submitted_at');
            $table->string('official_receipt_number')->nullable()->after('reseller_option');
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->text('autocount_invoice')->nullable()->after('timetec_proforma_invoice');
            $table->text('reseller_invoice')->nullable()->after('autocount_invoice');
            $table->string('autocount_invoice_number')->nullable()->after('reseller_invoice');
            $table->timestamp('aci_submitted_at')->nullable()->after('autocount_invoice_number');
            $table->string('reseller_option')->nullable()->after('aci_submitted_at');
            $table->string('official_receipt_number')->nullable()->after('reseller_option');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->dropColumn([
                'autocount_invoice',
                'reseller_invoice',
                'autocount_invoice_number',
                'aci_submitted_at',
                'reseller_option',
                'official_receipt_number',
            ]);
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->dropColumn([
                'autocount_invoice',
                'reseller_invoice',
                'autocount_invoice_number',
                'aci_submitted_at',
                'reseller_option',
                'official_receipt_number',
            ]);
        });
    }
};
