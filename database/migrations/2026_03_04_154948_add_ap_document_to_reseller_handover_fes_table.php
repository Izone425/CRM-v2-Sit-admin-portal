<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->string('ap_document')->nullable()->after('autocount_invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->dropColumn('ap_document');
        });
    }
};
