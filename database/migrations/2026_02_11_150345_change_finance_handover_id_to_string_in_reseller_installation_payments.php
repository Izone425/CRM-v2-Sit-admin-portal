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
        Schema::table('reseller_installation_payments', function (Blueprint $table) {
            $table->string('finance_handover_id', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_installation_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('finance_handover_id')->nullable()->change();
        });
    }
};
