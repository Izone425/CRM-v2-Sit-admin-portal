<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_renewal_reminders', function (Blueprint $table) {
            $table->string('f_invoice_no', 100)->nullable()->after('f_company_id');
            $table->dropUnique('reseller_company_unique');
            $table->unique(['reseller_id', 'f_company_id', 'f_invoice_no'], 'reseller_company_invoice_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_renewal_reminders', function (Blueprint $table) {
            $table->dropUnique('reseller_company_invoice_unique');
            $table->unique(['reseller_id', 'f_company_id'], 'reseller_company_unique');
            $table->dropColumn('f_invoice_no');
        });
    }
};
