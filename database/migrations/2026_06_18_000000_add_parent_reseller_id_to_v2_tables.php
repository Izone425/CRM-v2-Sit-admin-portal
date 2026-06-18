<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['reseller_v2', 'distributor_v2'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'parent_reseller_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->integer('parent_reseller_id')->nullable()->after('reseller_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['reseller_v2', 'distributor_v2'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'parent_reseller_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('parent_reseller_id');
                });
            }
        }
    }
};
