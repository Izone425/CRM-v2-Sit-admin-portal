<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->string('reseller_commission', 10)->default('disable')->after('bypass_invoice');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->dropColumn('reseller_commission');
        });
    }
};
