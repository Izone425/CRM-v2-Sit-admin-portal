<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activate_session_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->index();
            $table->unsignedBigInteger('software_handover_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->unsignedInteger('appointments_updated_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activate_session_logs');
    }
};
