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
        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->string('purchase_order')->nullable()->after('reseller_remark');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->dropColumn('purchase_order');
        });
    }
};
