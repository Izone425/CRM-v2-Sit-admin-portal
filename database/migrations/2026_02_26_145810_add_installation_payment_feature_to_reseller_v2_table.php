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
            $table->string('installation_payment_feature', 10)->default('disable')->after('trial_account_feature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->dropColumn('installation_payment_feature');
        });
    }
};
