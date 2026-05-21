<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->string('usd_with_invoice')->default('disable')->after('usd_with_quotation');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->dropColumn('usd_with_invoice');
        });
    }
};
