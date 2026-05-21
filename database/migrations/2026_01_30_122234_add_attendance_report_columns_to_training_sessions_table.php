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
        Schema::table('training_sessions', function (Blueprint $table) {
            // Attendance report JSON data (stores attendee list with join/leave times)
            $table->json('day1_attendance_report')->nullable()->after('day1_recording_fetched_at');
            $table->json('day2_attendance_report')->nullable()->after('day2_recording_fetched_at');
            $table->json('day3_attendance_report')->nullable()->after('day3_recording_fetched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'day1_attendance_report',
                'day2_attendance_report',
                'day3_attendance_report',
            ]);
        });
    }
};
