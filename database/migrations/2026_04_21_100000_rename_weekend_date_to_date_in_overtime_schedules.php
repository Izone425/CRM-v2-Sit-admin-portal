<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('overtime_schedules', function (Blueprint $table) {
            $table->renameColumn('weekend_date', 'date');
        });
    }

    public function down(): void
    {
        Schema::table('overtime_schedules', function (Blueprint $table) {
            $table->renameColumn('date', 'weekend_date');
        });
    }
};
