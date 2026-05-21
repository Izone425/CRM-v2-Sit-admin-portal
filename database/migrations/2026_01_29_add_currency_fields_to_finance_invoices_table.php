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
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->string('currency', 10)->default('MYR')->after('reseller_commission_amount');
            $table->decimal('currency_rate', 10, 4)->default(1.0000)->after('currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropColumn(['currency', 'currency_rate']);
        });
    }
};
