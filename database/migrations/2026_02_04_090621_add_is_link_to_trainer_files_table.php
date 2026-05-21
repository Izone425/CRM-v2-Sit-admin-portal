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
            $table->boolean('is_link')->default(false)->after('file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trainer_files', function (Blueprint $table) {
            $table->dropColumn('is_link');
        });
    }
};
