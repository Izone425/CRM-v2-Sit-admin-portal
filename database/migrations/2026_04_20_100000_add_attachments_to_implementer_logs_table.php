<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('implementer_logs', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('manual_follow_up_count');
        });
    }

    public function down(): void
    {
        Schema::table('implementer_logs', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
