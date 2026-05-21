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
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->text('self_billed_einvoice')->nullable()->after('reseller_payment_slip');
            $table->timestamp('self_billed_einvoice_submitted_at')->nullable()->after('self_billed_einvoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->dropColumn(['self_billed_einvoice', 'self_billed_einvoice_submitted_at']);
        });
    }
};
