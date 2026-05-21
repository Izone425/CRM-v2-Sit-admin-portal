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
        Schema::table('finance_handovers', function (Blueprint $table) {
            $table->string('payment_method', 20)->nullable()->after('reseller_invoice_number');
            $table->json('product_quotation')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_handovers', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'product_quotation']);
        });
    }
};
