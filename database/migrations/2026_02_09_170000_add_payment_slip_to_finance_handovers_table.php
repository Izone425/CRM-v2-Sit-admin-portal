<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_handovers', function (Blueprint $table) {
            $table->json('payment_slip')->nullable()->after('payment_by_customer');
        });
    }

    public function down(): void
    {
        Schema::table('finance_handovers', function (Blueprint $table) {
            $table->dropColumn('payment_slip');
        });
    }
};
