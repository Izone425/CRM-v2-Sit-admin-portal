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
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->string('usd_with_quotation')->default('disable')->after('bill_as_end_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->dropColumn('usd_with_quotation');
        });
    }
};
