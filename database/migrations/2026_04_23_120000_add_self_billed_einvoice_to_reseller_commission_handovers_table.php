<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_commission_handovers', function (Blueprint $table) {
            $table->string('self_billed_einvoice')->nullable()->after('payment_slip');
            $table->timestamp('self_billed_einvoice_uploaded_at')->nullable()->after('self_billed_einvoice');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_commission_handovers', function (Blueprint $table) {
            $table->dropColumn(['self_billed_einvoice', 'self_billed_einvoice_uploaded_at']);
        });
    }
};
