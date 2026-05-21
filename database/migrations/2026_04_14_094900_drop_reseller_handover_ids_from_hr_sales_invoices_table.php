<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_sales_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('hr_sales_invoices', 'reseller_software_handover_id')) {
                $table->dropColumn('reseller_software_handover_id');
            }
            if (Schema::hasColumn('hr_sales_invoices', 'reseller_handover_id')) {
                $table->dropColumn('reseller_handover_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_sales_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_software_handover_id')->nullable();
            $table->string('reseller_handover_id')->nullable();
        });
    }
};
