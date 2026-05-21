<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Legacy unique index on `date` may or may not exist; drop only if present.
        $indexes = collect(DB::select("SHOW INDEX FROM overtime_schedules"))
            ->pluck('Key_name')->unique()->all();
        if (in_array('overtime_schedules_date_unique', $indexes, true)) {
            DB::statement('ALTER TABLE overtime_schedules DROP INDEX overtime_schedules_date_unique');
        }

        // Add `type` column only if it isn't already there (idempotent).
        if (! Schema::hasColumn('overtime_schedules', 'type')) {
            Schema::table('overtime_schedules', function (Blueprint $table) {
                $table->string('type', 20)->default('main')->after('date');
            });
        }

        // Add composite unique only if not already present.
        if (! in_array('overtime_schedules_date_type_unique', $indexes, true)) {
            Schema::table('overtime_schedules', function (Blueprint $table) {
                $table->unique(['date', 'type']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('overtime_schedules', function (Blueprint $table) {
            $table->dropUnique(['date', 'type']);
            $table->dropColumn('type');
            $table->unique('date');
        });
    }
};
