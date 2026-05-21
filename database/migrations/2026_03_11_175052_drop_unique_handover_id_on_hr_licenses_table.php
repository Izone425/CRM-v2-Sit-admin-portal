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
        Schema::table('hr_licenses', function (Blueprint $table) {
            $table->dropUnique('hr_licenses_handover_id_unique');
            $table->index('handover_id');
        });
    }

    public function down(): void
    {
        Schema::table('hr_licenses', function (Blueprint $table) {
            $table->dropIndex(['handover_id']);
            $table->unique('handover_id');
        });
    }
};
