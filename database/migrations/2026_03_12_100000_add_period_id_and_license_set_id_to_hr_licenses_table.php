<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_licenses', function (Blueprint $table) {
            $table->string('period_id')->nullable()->after('auto_renewal');
            $table->string('license_set_id')->nullable()->after('period_id');
        });
    }

    public function down(): void
    {
        Schema::table('hr_licenses', function (Blueprint $table) {
            $table->dropColumn(['period_id', 'license_set_id']);
        });
    }
};
