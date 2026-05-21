<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handover_ffs', function (Blueprint $table) {
            $table->integer('vms_qty')->default(0)->after('qf_master_qty');
            $table->integer('patrol_qty')->default(0)->after('vms_qty');
            $table->integer('access_qty')->default(0)->after('patrol_qty');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_ffs', function (Blueprint $table) {
            $table->dropColumn(['vms_qty', 'patrol_qty', 'access_qty']);
        });
    }
};
