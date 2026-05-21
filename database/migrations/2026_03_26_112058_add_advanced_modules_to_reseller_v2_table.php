<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->string('advanced_modules')->default('disable')->after('reseller_commission');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_v2', function (Blueprint $table) {
            $table->dropColumn('advanced_modules');
        });
    }
};
