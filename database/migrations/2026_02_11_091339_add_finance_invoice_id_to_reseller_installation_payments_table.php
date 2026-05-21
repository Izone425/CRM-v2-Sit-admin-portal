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
            $table->unsignedBigInteger('finance_invoice_id')->nullable()->after('admin_remark');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_installation_payments', function (Blueprint $table) {
            $table->dropColumn('finance_invoice_id');
        });
    }
};
