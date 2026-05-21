<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->integer('qf_master_qty')->default(0)->after('payroll_qty');
        });

        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->integer('qf_master_qty')->default(0)->after('payroll_qty');
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->integer('qf_master_qty')->default(0)->after('payroll_qty');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->dropColumn('qf_master_qty');
        });

        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->dropColumn('qf_master_qty');
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->dropColumn('qf_master_qty');
        });
    }
};
