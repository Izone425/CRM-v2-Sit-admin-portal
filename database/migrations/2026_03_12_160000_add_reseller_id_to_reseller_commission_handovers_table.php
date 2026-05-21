<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_commission_handovers', function (Blueprint $table) {
            $table->string('reseller_id', 20)->nullable()->after('id');
            $table->index('reseller_id');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_commission_handovers', function (Blueprint $table) {
            $table->dropIndex(['reseller_id']);
            $table->dropColumn('reseller_id');
        });
    }
};
