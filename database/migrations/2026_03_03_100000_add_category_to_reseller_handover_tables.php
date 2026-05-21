<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->string('category')->nullable()->after('subscriber_status');
        });

        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->string('category')->nullable()->after('subscriber_status');
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->string('category')->nullable()->after('subscriber_status');
        });
    }

    public function down(): void
    {
        Schema::table('reseller_handovers', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('reseller_handover_fds', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('reseller_handover_fes', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
