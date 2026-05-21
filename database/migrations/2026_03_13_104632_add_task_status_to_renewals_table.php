<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->boolean('task_status')->default(false)->after('renewal_progress');
        });
    }

    public function down(): void
    {
        Schema::table('renewals', function (Blueprint $table) {
            $table->dropColumn('task_status');
        });
    }
};
