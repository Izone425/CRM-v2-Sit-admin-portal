<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handover_ffs', function (Blueprint $table) {
            $table->integer('fcc_qty')->default(0)->after('access_qty');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_ffs', function (Blueprint $table) {
            $table->dropColumn('fcc_qty');
        });
    }
};
