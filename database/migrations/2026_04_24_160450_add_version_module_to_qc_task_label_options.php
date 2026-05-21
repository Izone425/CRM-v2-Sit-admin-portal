<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_task_label_options', function (Blueprint $table) {
            $table->enum('hr_version', ['v1', 'v2'])->default('v1')->after('id');
            $table->string('module')->default('')->after('hr_version');
        });

        // Drop old unique (tier, value) and add new (hr_version, module, tier, value)
        // Wrap in try/catch because the constraint name may differ on some MySQL setups
        try {
            Schema::table('qc_task_label_options', function (Blueprint $table) {
                $table->dropUnique(['tier', 'value']);
            });
        } catch (\Throwable $e) {
            // Ignore — constraint name resolution may vary; fall through
        }

        Schema::table('qc_task_label_options', function (Blueprint $table) {
            $table->unique(['hr_version', 'module', 'tier', 'value'], 'qc_task_label_options_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::table('qc_task_label_options', function (Blueprint $table) {
            $table->dropUnique('qc_task_label_options_scope_unique');
        });

        Schema::table('qc_task_label_options', function (Blueprint $table) {
            $table->unique(['tier', 'value']);
            $table->dropColumn(['hr_version', 'module']);
        });
    }
};
