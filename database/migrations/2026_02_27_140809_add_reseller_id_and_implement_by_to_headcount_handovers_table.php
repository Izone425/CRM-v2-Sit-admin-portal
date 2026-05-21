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
        Schema::table('headcount_handovers', function (Blueprint $table) {
            $table->unsignedBigInteger('reseller_id')->nullable()->after('salesperson_remark');
            $table->string('implement_by')->nullable()->after('reseller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('headcount_handovers', function (Blueprint $table) {
            $table->dropColumn(['reseller_id', 'implement_by']);
        });
    }
};
