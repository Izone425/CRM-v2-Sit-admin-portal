<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handover_fgs', function (Blueprint $table) {
            $table->text('reseller_invoice')->nullable()->after('autocount_invoice');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_fgs', function (Blueprint $table) {
            $table->dropColumn('reseller_invoice');
        });
    }
};
