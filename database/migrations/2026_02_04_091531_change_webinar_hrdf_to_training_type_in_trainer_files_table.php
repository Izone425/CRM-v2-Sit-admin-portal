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
        Schema::table('trainer_files', function (Blueprint $table) {
            $table->dropColumn(['is_webinar', 'is_hrdf']);
            $table->json('training_type')->nullable()->after('is_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainer_files', function (Blueprint $table) {
            $table->dropColumn('training_type');
            $table->boolean('is_webinar')->default(false)->after('is_link');
            $table->boolean('is_hrdf')->default(false)->after('is_webinar');
        });
    }
};
