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
            // Online meeting IDs (MS Graph API ID for querying recordings)
            $table->string('day1_online_meeting_id')->nullable()->after('day1_meeting_password');
            $table->string('day2_online_meeting_id')->nullable()->after('day2_meeting_password');
            $table->string('day3_online_meeting_id')->nullable()->after('day3_meeting_password');

            // Recording links (S3 URLs)
            $table->text('day1_recording_link')->nullable()->after('day1_online_meeting_id');
            $table->text('day2_recording_link')->nullable()->after('day2_online_meeting_id');
            $table->text('day3_recording_link')->nullable()->after('day3_online_meeting_id');

            // Recording fetch timestamps
            $table->timestamp('day1_recording_fetched_at')->nullable()->after('day1_recording_link');
            $table->timestamp('day2_recording_fetched_at')->nullable()->after('day2_recording_link');
            $table->timestamp('day3_recording_fetched_at')->nullable()->after('day3_recording_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'day1_online_meeting_id',
                'day2_online_meeting_id',
                'day3_online_meeting_id',
                'day1_recording_link',
                'day2_recording_link',
                'day3_recording_link',
                'day1_recording_fetched_at',
                'day2_recording_fetched_at',
                'day3_recording_fetched_at',
            ]);
        });
    }
};
